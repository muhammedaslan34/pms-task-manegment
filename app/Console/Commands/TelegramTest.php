<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\TaskTelegramNotifier;
use App\Services\TelegramException;
use Illuminate\Console\Command;

class TelegramTest extends Command
{
    protected $signature = 'telegram:test {task? : Task id (default: the latest task)}';

    protected $description = 'Send the "new task" Telegram notification for a task right now and show every API result';

    public function handle(TaskTelegramNotifier $notifier): int
    {
        if (! $notifier->isConfigured()) {
            $this->error('Set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID first.');

            return self::FAILURE;
        }

        $id = $this->argument('task');
        $task = $id
            ? Task::with('images')->find($id)
            : Task::with('images')->latest('id')->first();

        if (! $task) {
            $this->error($id ? "Task #{$id} not found." : 'There are no tasks yet.');

            return self::FAILURE;
        }

        $this->info("Sending task #{$task->id} \"{$task->title}\" ({$task->images->count()} screenshot(s))...");

        $log = [];

        try {
            $notifier->notify($task, $log);
        } catch (TelegramException $exception) {
            $this->printLog($log);
            $this->error($exception->getMessage());

            if (str_contains($exception->getMessage(), 'chat not found')) {
                $this->line('The owner must open the bot in Telegram and press Start (or send /start) once before it can message them.');
            }

            return self::FAILURE;
        }

        $this->printLog($log);

        $failed = collect($log)->where('ok', false)->count();

        if ($failed > 0) {
            $this->warn("{$failed} step(s) failed; the details message was still delivered.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function printLog(array $log): void
    {
        $this->table(['Call', 'ok', 'Detail'], array_map(
            fn (array $entry) => [$entry['method'], $entry['ok'] ? 'true' : 'false', $entry['detail']],
            $log,
        ));
    }
}
