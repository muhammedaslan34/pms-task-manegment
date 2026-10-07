<?php

namespace App\Services;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskImage;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;

/**
 * Formats tasks as Telegram messages (HTML parse mode) and delivers the
 * "new task" notification: screenshots first, then the details message with
 * the status buttons (albums cannot carry an inline keyboard).
 */
class TaskTelegramNotifier
{
    use Localizable;

    /** Telegram limits (https://core.telegram.org/bots/api). */
    public const MESSAGE_LIMIT = 4096;

    public const CAPTION_LIMIT = 1024;

    public const MAX_PHOTO_BYTES = 10 * 1024 * 1024;

    public const MAX_UPLOAD_BYTES = 50 * 1024 * 1024;

    public const MEDIA_GROUP_LIMIT = 10;

    public function __construct(private readonly TelegramBot $bot)
    {
    }

    public function chatId(): ?string
    {
        $chatId = config('services.telegram.chat_id');

        return filled($chatId) ? (string) $chatId : null;
    }

    public function isConfigured(): bool
    {
        return $this->bot->isConfigured() && $this->chatId() !== null;
    }

    /**
     * Send the full "new task" notification to the owner's chat.
     *
     * Screenshot failures are reported and noted in the message; a failure of
     * the details message itself throws TelegramException.
     *
     * @param  list<array{method: string, ok: bool, detail: string}>  $log  filled with one entry per step, also when it throws
     * @return list<array{method: string, ok: bool, detail: string}> the same log
     */
    public function notify(Task $task, array &$log = []): array
    {
        if (! $this->isConfigured()) {
            return $log;
        }

        $task->loadMissing('images');
        $undelivered = $this->sendScreenshots($task, $log);

        try {
            $message = $this->bot->sendMessage(
                $this->chatId(),
                $this->renderTask($task, isNew: true, undeliveredScreenshots: $undelivered),
                $this->messageOptions($task),
            );
        } catch (TelegramException $exception) {
            $log[] = ['method' => 'sendMessage', 'ok' => false, 'detail' => $exception->getMessage()];

            throw $exception;
        }
        $log[] = ['method' => 'sendMessage', 'ok' => true, 'detail' => 'message_id '.($message['message_id'] ?? '?')];

        return $log;
    }

    /** Options for a task details message: HTML, no link preview, status buttons. */
    public function messageOptions(Task $task): array
    {
        return [
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
            'reply_markup' => $this->keyboard($task),
        ];
    }

    /** Inline keyboard: one button per status the task is not in, plus a dashboard link when reachable. */
    public function keyboard(Task $task): array
    {
        $rows = [];

        $statusButtons = [];
        foreach (TaskStatus::cases() as $status) {
            if ($status !== $task->status) {
                $statusButtons[] = [
                    'text' => $this->statusButtonLabel($status),
                    'callback_data' => "task:{$task->getKey()}:{$status->value}",
                ];
            }
        }
        $rows[] = $statusButtons;

        if ($url = $this->dashboardUrl($task)) {
            $rows[] = [['text' => '🖥 '.$this->translate('Open in dashboard'), 'url' => $url]];
        }

        return ['inline_keyboard' => $rows];
    }

    /**
     * The task details message.
     *
     * @param  bool  $isNew  "🆕 New task" header (notification) vs "📋 Task" (on demand)
     * @param  string|null  $footer  already-escaped HTML appended at the end (e.g. "status changed")
     */
    public function renderTask(Task $task, bool $isNew = true, ?string $footer = null, int $undeliveredScreenshots = 0): string
    {
        return $this->withLocale($this->locale(), function () use ($task, $isNew, $footer, $undeliveredScreenshots) {
            $render = function (?string $description) use ($task, $isNew, $footer, $undeliveredScreenshots): string {
                $header = $isNew
                    ? '🆕 <b>'.e(__('New task')).' #'.$task->getKey().'</b>'
                    : '📋 <b>'.e(__('Task')).' #'.$task->getKey().'</b>';

                $lines = [$header, '<b>'.$this->escape($task->title).'</b>', ''];

                if ($task->priority instanceof Priority) {
                    $lines[] = $this->priorityEmoji($task->priority).' <b>'.e(__('Priority')).':</b> '.e($task->priority->label());
                }
                if ($task->status instanceof TaskStatus) {
                    $lines[] = $this->statusEmoji($task->status).' <b>'.e(__('Status')).':</b> '.e($task->status->label());
                }
                $lines[] = '👤 <b>'.e(__('Submitted by')).':</b> '.($task->submitted_by ? $this->escape($task->submitted_by) : '—');
                if (filled($task->page_link)) {
                    $lines[] = '🔗 <b>'.e(__('Page')).':</b> '.$this->link($task->page_link);
                }
                if ($task->created_at) {
                    $lines[] = '🕒 <b>'.e(__('Created')).':</b> '.e($task->created_at->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i T'));
                }

                $imageCount = $task->relationLoaded('images') ? $task->images->count() : $task->images()->count();
                if ($imageCount > 0) {
                    $line = '🖼 <b>'.e(__('Screenshots')).':</b> '.$imageCount;
                    if ($undeliveredScreenshots > 0) {
                        $line .= ' ('.e(__(':count could not be attached', ['count' => $undeliveredScreenshots])).')';
                    }
                    $lines[] = $line;
                }

                if ($description !== null && $description !== '') {
                    $lines[] = '';
                    $lines[] = '📝 <b>'.e(__('Description')).'</b>';
                    $lines[] = '<blockquote expandable>'.$this->escape($description).'</blockquote>';
                }

                $lines[] = '';
                $adminUrl = route('admin.tasks.show', $task);
                $lines[] = '🛠 '.($this->dashboardUrl($task)
                    ? '<a href="'.$this->escape($adminUrl).'">'.e(__('Open in dashboard')).'</a>'
                    : e(__('Dashboard')).': <code>'.$this->escape($adminUrl).'</code>');

                if ($footer) {
                    $lines[] = '';
                    $lines[] = $footer;
                }

                return implode("\n", $lines);
            };

            return $this->fitDescription($render, (string) $task->description);
        });
    }

    /** Short HTML footer appended to a message after its status was changed from Telegram. */
    public function statusChangedFooter(TaskStatus $status): string
    {
        return $this->withLocale($this->locale(), fn () => '✏️ <i>'.e(__('Status changed to :status via Telegram', ['status' => $status->label()]))
            .' · '.e(now()->timezone(config('app.timezone'))->format('Y-m-d H:i')).'</i>');
    }

    public function statusEmoji(TaskStatus $status): string
    {
        return match ($status) {
            TaskStatus::Pending => '⏳',
            TaskStatus::InProgress => '🔄',
            TaskStatus::Completed => '✅',
        };
    }

    public function priorityEmoji(Priority $priority): string
    {
        return match ($priority) {
            Priority::Low => '🟢',
            Priority::Medium => '🟡',
            Priority::High => '🔴',
        };
    }

    public function statusLabel(TaskStatus $status): string
    {
        return $this->withLocale($this->locale(), fn () => $status->label());
    }

    public function translate(string $key, array $replace = []): string
    {
        return $this->withLocale($this->locale(), fn () => __($key, $replace));
    }

    /** Escape user content for Telegram's HTML parse mode (only &, <, > and " are special). */
    public function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
    }

    /** Admin URL for a URL button, or null when Telegram would reject it (localhost, http, ...). */
    public function dashboardUrl(Task $task): ?string
    {
        $url = route('admin.tasks.show', $task);

        return self::isPublicUrl($url, requireHttps: true) ? $url : null;
    }

    /** Whether Telegram can use this URL in a link/button: absolute http(s) URL on a public host. */
    public static function isPublicUrl(string $url, bool $requireHttps = false): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(trim($parts['host'] ?? '', '[]'));

        if ($host === '' || ! in_array($scheme, $requireHttps ? ['https'] : ['http', 'https'], true)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        if ($host === 'localhost' || ! str_contains($host, '.')) {
            return false;
        }

        return ! Str::endsWith($host, ['.localhost', '.local', '.test', '.internal', '.invalid', '.example', '.lan', '.home.arpa']);
    }

    private function statusButtonLabel(TaskStatus $status): string
    {
        return $this->statusEmoji($status).' '.$this->translate(match ($status) {
            TaskStatus::Completed => 'Done',
            TaskStatus::InProgress => 'In progress',
            TaskStatus::Pending => 'Pending',
        });
    }

    private function link(string $url): string
    {
        return self::isPublicUrl($url)
            ? '<a href="'.$this->escape($url).'">'.$this->escape(Str::limit($url, 120)).'</a>'
            : $this->escape($url);
    }

    /**
     * Render with as much of the description as fits Telegram's 4096-character
     * limit (measured after HTML parsing, in UTF-16 code units).
     *
     * @param  \Closure(?string): string  $render
     */
    private function fitDescription(\Closure $render, string $description): string
    {
        $description = trim($description);
        $html = $render($description);
        if ($description === '' || $this->visibleLength($html) <= self::MESSAGE_LIMIT) {
            return $html;
        }

        $available = self::MESSAGE_LIMIT - $this->visibleLength($render(null)) - 40;
        $chars = max(0, $available);

        while ($chars > 0) {
            $html = $render(rtrim(mb_substr($description, 0, $chars)).'…');
            if ($this->visibleLength($html) <= self::MESSAGE_LIMIT) {
                return $html;
            }
            $chars = (int) floor($chars * 0.9);
        }

        return $render(null);
    }

    /** Length Telegram counts: text without tags/entities, in UTF-16 code units. */
    private function visibleLength(string $html): int
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML401, 'UTF-8');

        return intdiv(strlen(mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')), 2);
    }

    /**
     * Upload the task's screenshots (raw bytes; the bucket is private).
     * One file → sendPhoto/sendDocument, 2–10 → sendMediaGroup, more → chunks of 10.
     * Photos and documents cannot share an album, so they are sent separately.
     *
     * @param  list<array{method: string, ok: bool, detail: string}>  $log
     * @return int number of screenshots that could not be delivered
     */
    private function sendScreenshots(Task $task, array &$log): int
    {
        $photos = [];
        $documents = [];
        $undelivered = 0;

        /** @var TaskImage $image */
        foreach ($task->images as $image) {
            $bytes = $image->contents();

            if ($bytes === null) {
                $undelivered++;
                $log[] = ['method' => 'storage', 'ok' => false, 'detail' => "screenshot #{$image->getKey()} could not be read from the screenshots disk"];

                continue;
            }

            if (strlen($bytes) > self::MAX_UPLOAD_BYTES) {
                $undelivered++;
                $log[] = ['method' => 'storage', 'ok' => false, 'detail' => "screenshot #{$image->getKey()} is larger than Telegram's 50 MB bot upload limit"];

                continue;
            }

            $item = [
                'bytes' => $bytes,
                'filename' => basename($image->path) ?: "screenshot-{$image->getKey()}.png",
            ];

            if ($this->fitsAsPhoto($bytes, $image->mimeType())) {
                $photos[] = $item + ['type' => 'photo'];
            } else {
                $documents[] = $item + ['type' => 'document'];
            }
        }

        $caption = $this->withLocale($this->locale(), fn () => '📎 <b>'.e(__('Task')).' #'.$task->getKey().'</b> — '
            .$this->escape(Str::limit($task->title, 200)));

        foreach ([$photos, $documents] as $group) {
            foreach (array_chunk($group, self::MEDIA_GROUP_LIMIT) as $chunk) {
                $method = count($chunk) > 1 ? 'sendMediaGroup' : ($chunk[0]['type'] === 'photo' ? 'sendPhoto' : 'sendDocument');

                try {
                    if ($method === 'sendMediaGroup') {
                        $chunk[0] += ['caption' => $caption, 'parse_mode' => 'HTML'];
                        $this->bot->sendMediaGroup($this->chatId(), $chunk);
                    } else {
                        $this->bot->{$method}($this->chatId(), $chunk[0]['bytes'], $chunk[0]['filename'], [
                            'caption' => $caption,
                            'parse_mode' => 'HTML',
                        ]);
                    }

                    $log[] = ['method' => $method, 'ok' => true, 'detail' => count($chunk).' file(s), '.number_format(array_sum(array_map(fn ($item) => strlen($item['bytes']), $chunk))).' bytes'];
                } catch (TelegramException $exception) {
                    report($exception);
                    $undelivered += count($chunk);
                    $log[] = ['method' => $method, 'ok' => false, 'detail' => $exception->getMessage()];
                }
            }
        }

        return $undelivered;
    }

    /** sendPhoto limits: ≤10 MB, width+height ≤ 10000, aspect ratio ≤ 20; GIFs go as documents to stay animated. */
    private function fitsAsPhoto(string $bytes, string $mimeType): bool
    {
        if (strlen($bytes) > self::MAX_PHOTO_BYTES || $mimeType === 'image/gif') {
            return false;
        }

        $size = @getimagesizefromstring($bytes);
        if ($size === false || ! in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return false;
        }

        [$width, $height] = $size;

        return $width > 0 && $height > 0
            && $width + $height <= 10000
            && max($width, $height) / min($width, $height) <= 20;
    }

    private function locale(): string
    {
        return config('services.telegram.locale') ?: config('app.locale');
    }
}
