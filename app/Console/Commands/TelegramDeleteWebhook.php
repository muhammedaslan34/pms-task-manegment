<?php

namespace App\Console\Commands;

use App\Services\TelegramBot;
use App\Services\TelegramException;
use Illuminate\Console\Command;

class TelegramDeleteWebhook extends Command
{
    protected $signature = 'telegram:delete-webhook {--drop-pending : Also discard queued updates}';

    protected $description = 'Remove the Telegram webhook (telegram:poll does this automatically)';

    public function handle(TelegramBot $bot): int
    {
        if (! $bot->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        try {
            $bot->deleteWebhook((bool) $this->option('drop-pending'));
        } catch (TelegramException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Webhook removed.');

        return self::SUCCESS;
    }
}
