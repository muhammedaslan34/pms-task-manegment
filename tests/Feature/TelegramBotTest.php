<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Events\TaskSubmitted;
use App\Livewire\Tasks\Create;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    private const CHAT_ID = '578819258';

    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => self::CHAT_ID,
            'services.telegram.webhook_secret' => self::SECRET,
            'services.telegram.locale' => 'en',
        ]);

        Storage::fake('public');
        Http::preventStrayRequests();
        // Run defer()red callbacks (sending after the response) immediately.
        $this->withoutDefer();
    }

    private function fakeTelegram(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 321]])]);
    }

    private static function isMethod(Request $request, string $method): bool
    {
        return str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/'.$method);
    }

    /** Value of a multipart form field. */
    private static function part(Request $request, string $name): ?string
    {
        $part = collect($request->data())->firstWhere('name', $name);

        return $part['contents'] ?? null;
    }

    private static function callbackData(array $replyMarkup): array
    {
        return collect($replyMarkup['inline_keyboard'])->flatten(1)->pluck('callback_data')->filter()->values()->all();
    }

    private function callbackUpdate(Task $task, string $action, string $chatId = self::CHAT_ID): array
    {
        return [
            'update_id' => 1001,
            'callback_query' => [
                'id' => 'cbq-1',
                'from' => ['id' => (int) $chatId, 'is_bot' => false, 'first_name' => 'Owner'],
                'message' => [
                    'message_id' => 55,
                    'chat' => ['id' => (int) $chatId, 'type' => 'private'],
                    'date' => time(),
                    'text' => "🆕 New task #{$task->id}\n{$task->title}",
                ],
                'chat_instance' => '1',
                'data' => "task:{$task->id}:{$action}",
            ],
        ];
    }

    private function postWebhook(array $update, ?string $secret = self::SECRET)
    {
        return $this->postJson('/telegram/webhook', $update, $secret === null ? [] : ['X-Telegram-Bot-Api-Secret-Token' => $secret]);
    }

    public function test_listener_sends_task_details_with_status_buttons(): void
    {
        $this->fakeTelegram();
        $task = Task::factory()->create([
            'title' => 'Checkout <b>broken</b> & "slow"',
            'status' => TaskStatus::Pending,
            'priority' => 'high',
            'submitted_by' => 'jane@example.com',
            'page_link' => 'https://shop.acme.io/checkout',
        ]);

        TaskSubmitted::dispatch($task);

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($task) {
            if (! self::isMethod($request, 'sendMessage')) {
                return false;
            }

            $this->assertSame(self::CHAT_ID, (string) $request['chat_id']);
            $this->assertSame('HTML', $request['parse_mode']);
            $this->assertStringContainsString("🆕 <b>New task #{$task->id}</b>", $request['text']);
            $this->assertStringContainsString('Checkout &lt;b&gt;broken&lt;/b&gt; &amp; &quot;slow&quot;', $request['text']);
            $this->assertStringContainsString('🔴 <b>Priority:</b> High', $request['text']);
            $this->assertStringContainsString('jane@example.com', $request['text']);
            $this->assertStringContainsString('<a href="https://shop.acme.io/checkout">', $request['text']);
            $this->assertStringContainsString(route('admin.tasks.show', $task), $request['text']);
            $this->assertSame(
                ["task:{$task->id}:in_progress", "task:{$task->id}:completed"],
                self::callbackData($request['reply_markup']),
            );
            // APP_URL is http://localhost in tests: Telegram rejects such URL buttons, so none is sent.
            $this->assertStringNotContainsString('"url"', json_encode($request['reply_markup']));

            return true;
        });
    }

    public function test_dashboard_url_button_is_added_for_a_public_https_app_url(): void
    {
        $this->fakeTelegram();
        URL::forceRootUrl('https://pms.acme.io');
        URL::forceScheme('https');
        $task = Task::factory()->create(['status' => TaskStatus::InProgress]);

        TaskSubmitted::dispatch($task);

        Http::assertSent(function (Request $request) use ($task) {
            $buttons = collect($request['reply_markup']['inline_keyboard'])->flatten(1);

            return $buttons->pluck('url')->filter()->values()->all() === ["https://pms.acme.io/admin/tasks/{$task->id}"]
                && self::callbackData($request['reply_markup']) === ["task:{$task->id}:pending", "task:{$task->id}:completed"];
        });
    }

    public function test_submitting_the_form_sends_screenshots_as_a_media_group_then_the_details(): void
    {
        $this->fakeTelegram();

        Livewire::test(Create::class)
            ->set('title', 'Telegram flow test')
            ->set('description', 'Steps to reproduce')
            ->set('priority', 'medium')
            ->set('screenshots', [
                UploadedFile::fake()->image('first.png', 120, 80),
                UploadedFile::fake()->image('second.jpg', 80, 120),
            ])
            ->call('save')
            ->assertHasNoErrors();

        $task = Task::where('title', 'Telegram flow test')->firstOrFail();
        $this->assertCount(2, $task->images);

        $recorded = Http::recorded()->map(fn ($pair) => basename(parse_url($pair[0]->url(), PHP_URL_PATH)))->all();
        $this->assertSame(['sendMediaGroup', 'sendMessage'], $recorded);

        Http::assertSent(function (Request $request) use ($task) {
            if (! self::isMethod($request, 'sendMediaGroup')) {
                return false;
            }

            $this->assertTrue($request->isMultipart());
            $media = json_decode(self::part($request, 'media'), true);
            $this->assertSame(['photo', 'photo'], array_column($media, 'type'));
            $this->assertSame(['attach://file0', 'attach://file1'], array_column($media, 'media'));
            $this->assertStringContainsString("#{$task->id}", $media[0]['caption']);
            // Raw bytes are uploaded (private bucket), not URLs.
            $this->assertSame(Storage::disk('public')->get($task->images[0]->path), self::part($request, 'file0'));

            return true;
        });
    }

    public function test_single_screenshot_uses_send_photo_and_oversized_ratio_falls_back_to_document(): void
    {
        $this->fakeTelegram();
        $task = Task::factory()->create();

        foreach (['normal.png' => [200, 100], 'very-tall.png' => [50, 2000]] as $name => [$width, $height]) {
            $path = "screenshots/{$name}";
            Storage::disk('public')->put($path, UploadedFile::fake()->image($name, $width, $height)->getContent());
            $task->images()->create(['path' => $path]);
        }

        TaskSubmitted::dispatch($task);

        $recorded = Http::recorded()->map(fn ($pair) => basename(parse_url($pair[0]->url(), PHP_URL_PATH)))->all();
        $this->assertSame(['sendPhoto', 'sendDocument', 'sendMessage'], $recorded);
    }

    public function test_telegram_failures_never_break_task_submission(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 500, 'description' => 'Internal'], 500)]);

        Livewire::test(Create::class)
            ->set('title', 'Still saved')
            ->set('priority', 'low')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', ['title' => 'Still saved']);
    }

    public function test_nothing_is_sent_when_telegram_is_not_configured(): void
    {
        Http::fake();
        config(['services.telegram.bot_token' => null]);

        TaskSubmitted::dispatch(Task::factory()->create());

        Http::assertNothingSent();
    }

    public function test_long_descriptions_are_truncated_to_telegrams_limit(): void
    {
        $this->fakeTelegram();
        $task = Task::factory()->create(['description' => str_repeat('Lorem ipsum <dolor> & sit amet. ', 400)]);

        TaskSubmitted::dispatch($task);

        Http::assertSent(function (Request $request) {
            $visible = html_entity_decode(strip_tags($request['text']), ENT_QUOTES | ENT_HTML401, 'UTF-8');

            return mb_strlen($visible) <= 4096 && str_contains($request['text'], '…</blockquote>');
        });
    }

    public function test_button_press_from_the_owner_chat_updates_status_and_edits_the_message(): void
    {
        $this->fakeTelegram();
        $task = Task::factory()->create(['status' => TaskStatus::Pending, 'completed_at' => null, 'resolution_note' => null]);

        $this->postWebhook($this->callbackUpdate($task, 'completed'))->assertOk();

        $task->refresh();
        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertNotNull($task->completed_at);

        Http::assertSent(fn (Request $request) => self::isMethod($request, 'answerCallbackQuery')
            && $request['callback_query_id'] === 'cbq-1'
            && str_contains($request['text'], "Task #{$task->id} marked as Completed"));

        Http::assertSent(function (Request $request) use ($task) {
            if (! self::isMethod($request, 'editMessageText')) {
                return false;
            }

            $this->assertSame(55, $request['message_id']);
            $this->assertStringContainsString("🆕 <b>New task #{$task->id}</b>", $request['text']);
            $this->assertStringContainsString('✅ <b>Status:</b> Completed', $request['text']);
            $this->assertStringContainsString('Status changed to Completed via Telegram', $request['text']);
            $this->assertSame(
                ["task:{$task->id}:pending", "task:{$task->id}:in_progress"],
                self::callbackData($request['reply_markup']),
            );

            return true;
        });
    }

    public function test_button_press_from_another_chat_is_ignored(): void
    {
        $this->fakeTelegram();
        $task = Task::factory()->create(['status' => TaskStatus::Pending]);

        $this->postWebhook($this->callbackUpdate($task, 'completed', chatId: '111222333'))->assertOk();

        $this->assertSame(TaskStatus::Pending, $task->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_webhook_rejects_a_missing_or_wrong_secret(): void
    {
        $this->fakeTelegram();
        $task = Task::factory()->create(['status' => TaskStatus::Pending]);

        $this->postWebhook($this->callbackUpdate($task, 'completed'), secret: 'wrong')->assertForbidden();
        $this->postWebhook($this->callbackUpdate($task, 'completed'), secret: null)->assertForbidden();

        config(['services.telegram.webhook_secret' => null]);
        $this->postWebhook($this->callbackUpdate($task, 'completed'), secret: '')->assertForbidden();

        $this->assertSame(TaskStatus::Pending, $task->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_tasks_command_lists_open_tasks_for_the_owner_only(): void
    {
        $this->fakeTelegram();
        $open = Task::factory()->create(['status' => TaskStatus::InProgress, 'title' => 'Open one']);
        Task::factory()->create(['status' => TaskStatus::Completed, 'title' => 'Closed one']);

        $message = fn (string $chatId) => ['update_id' => 7, 'message' => [
            'message_id' => 9, 'date' => time(), 'text' => '/tasks', 'chat' => ['id' => (int) $chatId, 'type' => 'private'],
        ]];

        $this->postWebhook($message('111222333'))->assertOk();
        Http::assertNothingSent();

        $this->postWebhook($message(self::CHAT_ID))->assertOk();
        Http::assertSent(fn (Request $request) => self::isMethod($request, 'sendMessage')
            && str_contains($request['text'], 'Open one')
            && ! str_contains($request['text'], 'Closed one')
            && self::callbackData($request['reply_markup']) === ["task:{$open->id}:show"]);
    }

    public function test_poll_command_handles_updates_through_the_same_handler(): void
    {
        $task = Task::factory()->create(['status' => TaskStatus::Pending]);

        Http::fake([
            'api.telegram.org/*/getUpdates' => Http::sequence()
                ->push(['ok' => true, 'result' => [$this->callbackUpdate($task, 'in_progress')]])
                ->push(['ok' => true, 'result' => []]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $this->artisan('telegram:poll', ['--once' => true, '--timeout' => 0])->assertSuccessful();

        $this->assertSame(TaskStatus::InProgress, $task->fresh()->status);
        Http::assertSent(fn (Request $request) => self::isMethod($request, 'deleteWebhook'));
        // The final getUpdates confirms the processed batch.
        Http::assertSent(fn (Request $request) => self::isMethod($request, 'getUpdates') && ($request->data()['offset'] ?? null) === 1002);
    }

    public function test_webhook_is_stateless_and_acknowledges_immediately(): void
    {
        $this->fakeTelegram();
        $task = Task::factory()->create(['status' => TaskStatus::Pending]);

        $response = $this->postWebhook($this->callbackUpdate($task, 'in_progress'))
            ->assertOk()
            ->assertExactJson(['ok' => true]);

        // No CSRF failure, no session/cookies for Telegram's requests.
        $this->assertSame([], $response->headers->getCookies());
        $this->assertSame(TaskStatus::InProgress, $task->fresh()->status);
    }

    public function test_set_webhook_registers_the_url_with_secret_and_allowed_updates(): void
    {
        Http::fake([
            'api.telegram.org/*/getWebhookInfo' => Http::response(['ok' => true, 'result' => [
                'url' => 'https://abc.trycloudflare.com/telegram/webhook', 'pending_update_count' => 0,
            ]]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => true]),
        ]);

        // A bare base URL (e.g. a tunnel) gets the webhook route path appended.
        $this->artisan('telegram:set-webhook', ['--url' => 'https://abc.trycloudflare.com'])
            ->expectsOutputToContain('Webhook set: https://abc.trycloudflare.com/telegram/webhook')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request) => self::isMethod($request, 'setWebhook')
            && $request['url'] === 'https://abc.trycloudflare.com/telegram/webhook'
            && $request['secret_token'] === self::SECRET
            && $request['allowed_updates'] === ['message', 'callback_query']);
    }

    public function test_set_webhook_refuses_non_public_urls_without_calling_telegram(): void
    {
        Http::fake();

        $this->artisan('telegram:set-webhook')->assertFailed(); // APP_URL is http://localhost in tests
        $this->artisan('telegram:set-webhook', ['--url' => 'https://localhost:8443'])->assertFailed();

        Http::assertNothingSent();
    }

    public function test_webhook_info_shows_bot_and_webhook_status(): void
    {
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['id' => 1, 'username' => 'test_bot', 'first_name' => 'Test']]),
            'api.telegram.org/*/getWebhookInfo' => Http::response(['ok' => true, 'result' => [
                'url' => 'https://abc.trycloudflare.com/telegram/webhook', 'pending_update_count' => 2,
            ]]),
        ]);

        $this->artisan('telegram:webhook-info')
            ->expectsOutputToContain('@test_bot')
            ->expectsOutputToContain('https://abc.trycloudflare.com/telegram/webhook')
            ->assertSuccessful();
    }
}
