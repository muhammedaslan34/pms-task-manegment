<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Mcp\Prompts\ImplementTask;
use App\Mcp\Servers\TasksServer;
use App\Mcp\Tools\CompleteTask;
use App\Mcp\Tools\CreateTask;
use App\Mcp\Tools\GetTask;
use App\Mcp\Tools\ListTasks;
use App\Mcp\Tools\StartTask;
use App\Mcp\Tools\UpdateTaskStatus;
use App\Events\TaskSubmitted;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Server\Testing\TestResponse;
use Tests\TestCase;

class TasksMcpServerTest extends TestCase
{
    use RefreshDatabase;

    private function task(array $attributes = []): Task
    {
        // Fixed text fields so search assertions don't depend on random factory data.
        return Task::factory()->create([
            'description' => 'Something is not working as expected.',
            'page_link' => 'https://example.com/page',
            'submitted_by' => 'reporter@example.com',
            'status' => TaskStatus::Pending,
            'resolution_note' => null,
            'completed_at' => null,
            ...$attributes,
        ]);
    }

    /** @return array<string, mixed> */
    private function decodeFirstText(TestResponse $response): array
    {
        $raw = (fn () => $this->response->toArray())->call($response);

        return json_decode($raw['result']['content'][0]['text'], true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array<int, array<string, mixed>> */
    private function content(TestResponse $response): array
    {
        return (fn () => $this->response->toArray())->call($response)['result']['content'];
    }

    public function test_list_tasks_returns_open_tasks_ordered_by_priority_then_oldest(): void
    {
        $oldLow = $this->task(['title' => 'Old low', 'priority' => Priority::Low, 'created_at' => now()->subDays(9)]);
        $newHigh = $this->task(['title' => 'New high', 'priority' => Priority::High, 'created_at' => now()->subDay()]);
        $oldHigh = $this->task(['title' => 'Old high', 'priority' => Priority::High, 'created_at' => now()->subDays(5), 'status' => TaskStatus::InProgress]);
        $medium = $this->task(['title' => 'Medium one', 'priority' => Priority::Medium]);
        $this->task(['title' => 'Done already', 'priority' => Priority::High, 'status' => TaskStatus::Completed]);

        $response = TasksServer::tool(ListTasks::class)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Old high')
            ->assertDontSee('Done already');

        $data = $this->decodeFirstText($response);

        $this->assertSame(4, $data['total']);
        $this->assertSame(
            [$oldHigh->id, $newHigh->id, $medium->id, $oldLow->id],
            array_column($data['tasks'], 'id'),
        );
        $this->assertSame(
            ['id', 'title', 'priority', 'status', 'submitted_by', 'created_at', 'image_count'],
            array_keys($data['tasks'][0]),
        );
    }

    public function test_list_tasks_filters_by_status_priority_search_and_limit(): void
    {
        $this->task(['title' => 'Checkout crash', 'priority' => Priority::High]);
        $this->task(['title' => 'Login typo', 'priority' => Priority::Low]);
        $this->task(['title' => 'Checkout layout', 'priority' => Priority::Low, 'status' => TaskStatus::Completed]);

        $completed = $this->decodeFirstText(TasksServer::tool(ListTasks::class, ['status' => 'completed']));
        $this->assertSame(['Checkout layout'], array_column($completed['tasks'], 'title'));

        $search = $this->decodeFirstText(TasksServer::tool(ListTasks::class, ['status' => 'all', 'search' => 'checkout']));
        $this->assertEqualsCanonicalizing(['Checkout crash', 'Checkout layout'], array_column($search['tasks'], 'title'));

        $low = $this->decodeFirstText(TasksServer::tool(ListTasks::class, ['priority' => 'low']));
        $this->assertSame(['Login typo'], array_column($low['tasks'], 'title'));

        $limited = $this->decodeFirstText(TasksServer::tool(ListTasks::class, ['status' => 'all', 'limit' => 1]));
        $this->assertSame(3, $limited['total']);
        $this->assertSame(1, $limited['returned']);

        TasksServer::tool(ListTasks::class, ['status' => 'bogus'])->assertHasErrors();
    }

    public function test_get_task_returns_details_and_embeds_screenshots_as_images(): void
    {
        Storage::fake('public');

        $task = $this->task([
            'title' => 'Broken cart',
            'description' => 'The cart total is wrong after applying a coupon.',
            'page_link' => 'https://example.com/cart',
        ]);

        $png = UploadedFile::fake()->image('shot.png', 20, 20)->getContent();
        Storage::disk('public')->put('task-images/shot.png', $png);
        $task->images()->create(['path' => 'task-images/shot.png']);
        $task->images()->create(['path' => 'task-images/missing.jpg']);

        $response = TasksServer::tool(GetTask::class, ['id' => $task->id])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee(['Broken cart', 'The cart total is wrong after applying a coupon.', 'https://example.com/cart']);

        $content = $this->content($response);
        $images = array_values(array_filter($content, fn (array $item) => $item['type'] === 'image'));

        $this->assertCount(1, $images);
        $this->assertSame('image/png', $images[0]['mimeType']);
        $this->assertSame($png, base64_decode($images[0]['data']));

        $details = $this->decodeFirstText($response);
        $this->assertCount(2, $details['screenshots']);
        $this->assertTrue($details['screenshots'][0]['embedded']);
        $this->assertFalse($details['screenshots'][1]['embedded']);
        $this->assertNotEmpty($details['screenshots'][1]['url']);
        $this->assertSame('pending', $details['status']);
    }

    public function test_get_task_can_skip_images_and_reports_unknown_ids(): void
    {
        Storage::fake('public');

        $task = $this->task();
        Storage::disk('public')->put('task-images/a.png', UploadedFile::fake()->image('a.png')->getContent());
        $task->images()->create(['path' => 'task-images/a.png']);

        $content = $this->content(TasksServer::tool(GetTask::class, ['id' => $task->id, 'include_images' => false]));
        $this->assertSame(['text'], array_column($content, 'type'));

        TasksServer::tool(GetTask::class, ['id' => 999999])->assertHasErrors(['Task #999999 not found']);
        TasksServer::tool(GetTask::class, [])->assertHasErrors();
    }

    public function test_start_task_marks_it_in_progress(): void
    {
        $task = $this->task();

        TasksServer::tool(StartTask::class, ['id' => $task->id])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('moved from pending to in_progress');

        $this->assertSame(TaskStatus::InProgress, $task->fresh()->status);
    }

    public function test_start_task_refuses_to_reopen_completed_tasks(): void
    {
        $task = $this->task(['status' => TaskStatus::Completed, 'completed_at' => now()]);

        TasksServer::tool(StartTask::class, ['id' => $task->id])->assertHasErrors(['already completed']);

        $this->assertSame(TaskStatus::Completed, $task->fresh()->status);
    }

    public function test_complete_task_requires_a_resolution_note_and_sets_completed_at(): void
    {
        $task = $this->task(['status' => TaskStatus::InProgress]);

        TasksServer::tool(CompleteTask::class, ['id' => $task->id])->assertHasErrors();
        $this->assertSame(TaskStatus::InProgress, $task->fresh()->status);

        TasksServer::tool(CompleteTask::class, [
            'id' => $task->id,
            'resolution_note' => 'Fixed coupon rounding in CartTotal::calculate and added a test.',
        ])->assertOk()->assertHasNoErrors()->assertSee('moved from in_progress to completed');

        $task->refresh();
        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertSame('Fixed coupon rounding in CartTotal::calculate and added a test.', $task->resolution_note);
        $this->assertNotNull($task->completed_at);
    }

    public function test_update_task_status_can_move_a_task_back_to_pending(): void
    {
        $task = $this->task(['status' => TaskStatus::Completed, 'completed_at' => now(), 'resolution_note' => 'Done']);

        TasksServer::tool(UpdateTaskStatus::class, ['id' => $task->id, 'status' => 'completed'])->assertHasErrors();

        TasksServer::tool(UpdateTaskStatus::class, [
            'id' => $task->id,
            'status' => 'pending',
            'note' => 'Reopened: the fix did not cover the mobile layout.',
        ])->assertOk()->assertHasNoErrors();

        $task->refresh();
        $this->assertSame(TaskStatus::Pending, $task->status);
        $this->assertNull($task->completed_at);
        $this->assertSame('Reopened: the fix did not cover the mobile layout.', $task->resolution_note);
    }

    public function test_create_task_stores_the_task_and_downloads_its_screenshots(): void
    {
        Storage::fake('public');
        Event::fake([TaskSubmitted::class]);

        $png = UploadedFile::fake()->image('shot.png', 20, 20)->getContent();
        Http::fake([
            '93.184.215.14/one.png*' => Http::response($png, 200, ['Content-Type' => 'image/png']),
            '93.184.215.14/two*' => Http::response($png, 200, ['Content-Type' => 'application/octet-stream']),
        ]);

        $response = TasksServer::tool(CreateTask::class, [
            'title' => 'Daily revenue report',
            'description' => 'Add the search filters and table columns.',
            'page_link' => 'https://example.com/reports/daily',
            'priority' => 'high',
            'submitted_by' => 'reporter@example.com',
            'image_urls' => ['https://93.184.215.14/one.png?X-Amz-Signature=abc', 'https://93.184.215.14/two'],
        ])->assertOk()->assertHasNoErrors();

        $data = $this->decodeFirstText($response);
        $task = Task::with('images')->findOrFail($data['task']['id']);

        $this->assertSame('Daily revenue report', $task->title);
        $this->assertSame(Priority::High, $task->priority);
        $this->assertSame(TaskStatus::Pending, $task->status);
        $this->assertSame('https://example.com/reports/daily', $task->page_link);
        $this->assertCount(2, $task->images);
        $this->assertSame(2, $data['task']['image_count']);
        foreach ($task->images as $image) {
            $this->assertStringStartsWith('screenshots/', $image->path);
            $this->assertStringEndsWith('.png', $image->path);
            $this->assertSame($png, Storage::disk('public')->get($image->path));
        }
        Event::assertDispatched(TaskSubmitted::class, fn (TaskSubmitted $event) => $event->task->is($task));
    }

    public function test_create_task_defaults_to_medium_and_can_skip_the_notification(): void
    {
        Event::fake([TaskSubmitted::class]);

        $data = $this->decodeFirstText(
            TasksServer::tool(CreateTask::class, ['title' => 'No screenshots', 'notify' => false])->assertOk()
        );

        $task = Task::findOrFail($data['task']['id']);
        $this->assertSame(Priority::Medium, $task->priority);
        $this->assertFalse($task->hasImages());
        Event::assertNotDispatched(TaskSubmitted::class);
    }

    public function test_create_task_creates_nothing_when_a_screenshot_cannot_be_used(): void
    {
        Storage::fake('public');
        Event::fake([TaskSubmitted::class]);

        $png = UploadedFile::fake()->image('shot.png')->getContent();
        Http::fake([
            '93.184.215.14/ok.png' => Http::response($png, 200, ['Content-Type' => 'image/png']),
            '93.184.215.14/page.html' => Http::response('<html></html>', 200, ['Content-Type' => 'text/html']),
            '93.184.215.14/gone.png' => Http::response('', 404),
        ]);

        TasksServer::tool(CreateTask::class, [
            'title' => 'Bad screenshot',
            'image_urls' => ['https://93.184.215.14/ok.png', 'https://93.184.215.14/page.html'],
        ])->assertHasErrors(['Screenshot 2 is not a png, jpg, gif or webp image.']);

        TasksServer::tool(CreateTask::class, [
            'title' => 'Missing screenshot',
            'image_urls' => ['https://93.184.215.14/gone.png'],
        ])->assertHasErrors(['Screenshot 1 could not be downloaded (HTTP 404).']);

        TasksServer::tool(CreateTask::class, [
            'title' => 'Internal screenshot',
            'image_urls' => ['https://127.0.0.1/secret.png'],
        ])->assertHasErrors(['is not a public address']);

        TasksServer::tool(CreateTask::class, [
            'title' => 'Plain http',
            'image_urls' => ['http://93.184.215.14/ok.png'],
        ])->assertHasErrors();

        TasksServer::tool(CreateTask::class, ['title' => 'x'])->assertHasErrors();

        $this->assertSame(0, Task::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        Event::assertNotDispatched(TaskSubmitted::class);
    }

    public function test_server_exposes_the_expected_tools_and_prompt(): void
    {
        TasksServer::prompt(ImplementTask::class, ['id' => 7])
            ->assertOk()
            ->assertSee('get_task with id 7');

        $this->assertSame('list_tasks', (new ListTasks)->name());
        $this->assertSame('get_task', (new GetTask)->name());
        $this->assertSame('start_task', (new StartTask)->name());
        $this->assertSame('complete_task', (new CompleteTask)->name());
        $this->assertSame('update_task_status', (new UpdateTaskStatus)->name());
        $this->assertSame('create_task', (new CreateTask)->name());
    }
}
