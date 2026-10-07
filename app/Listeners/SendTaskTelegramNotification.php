<?php

namespace App\Listeners;

use App\Events\TaskSubmitted;
use App\Models\Task;
use App\Services\TaskTelegramNotifier;

use function Illuminate\Support\defer;

/**
 * Posts newly submitted tasks to the owner's Telegram chat.
 *
 * Runs after the HTTP response has been sent (defer), so the submit form stays
 * fast and no queue worker is needed. Failures are reported, never thrown, so
 * Telegram problems can't break task submission. Skips silently when the bot
 * token or chat id is not configured.
 */
class SendTaskTelegramNotification
{
    public function __construct(private readonly TaskTelegramNotifier $notifier)
    {
    }

    public function handle(TaskSubmitted $event): void
    {
        try {
            if (! $this->notifier->isConfigured()) {
                return;
            }

            $taskId = $event->task->getKey();

            defer(function () use ($taskId) {
                try {
                    $task = Task::with('images')->find($taskId);

                    if ($task) {
                        $this->notifier->notify($task);
                    }
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }, 'telegram-task-'.$taskId);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
