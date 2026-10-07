<?php

namespace App\Console\Commands;

use App\Services\TelegramBot;
use App\Services\TelegramException;
use App\Services\TelegramUpdateHandler;
use Illuminate\Console\Command;

/**
 * Long-polling delivery mode for local development, where Telegram cannot
 * reach APP_URL. getUpdates does not work while a webhook is set, so the
 * webhook is removed first (run telegram:set-webhook again in production).
 */
class TelegramPoll extends Command
{
    protected $signature = 'telegram:poll
        {--timeout=30 : Long-poll timeout in seconds}
        {--once : Fetch and handle a single batch of updates, then exit}';

    protected $description = 'Receive Telegram updates by long polling (local alternative to the webhook)';

    public function handle(TelegramBot $bot, TelegramUpdateHandler $handler): int
    {
        if (! $bot->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        try {
            $bot->deleteWebhook();
            $bot->setMyCommands(TelegramSetWebhook::commands());
        } catch (TelegramException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $timeout = max(0, (int) $this->option('timeout'));
        $once = (bool) $this->option('once');
        $offset = null;

        $this->info('Polling Telegram for updates'.($once ? ' (single batch)' : '; press Ctrl+C to stop').'...');

        do {
            try {
                $updates = $bot->getUpdates($offset, $timeout);
            } catch (TelegramException $exception) {
                $this->warn($exception->getMessage());
                if ($once) {
                    return self::FAILURE;
                }
                sleep(5);

                continue;
            }

            foreach ($updates as $update) {
                // Advance even if handling fails, so one bad update can't block the loop.
                $offset = $update['update_id'] + 1;

                try {
                    $handler->handle($update);
                    $this->line(sprintf('[%s] handled update %d (%s)', now()->format('H:i:s'), $update['update_id'], $this->describe($update)));
                } catch (\Throwable $exception) {
                    report($exception);
                    $this->warn("Update {$update['update_id']} failed: {$exception->getMessage()}");
                }
            }
        } while (! $once);

        // Confirm the last batch so it isn't delivered again on the next run.
        if ($offset !== null) {
            try {
                $bot->getUpdates($offset, 0);
            } catch (TelegramException) {
                // The batch will be redelivered; TelegramUpdateHandler skips update ids it already handled.
            }
        }

        return self::SUCCESS;
    }

    private function describe(array $update): string
    {
        return match (true) {
            isset($update['callback_query']) => 'button '.($update['callback_query']['data'] ?? '?'),
            isset($update['message']['text']) => 'message '.mb_strimwidth($update['message']['text'], 0, 40, '…'),
            default => implode(',', array_diff(array_keys($update), ['update_id'])),
        };
    }
}
