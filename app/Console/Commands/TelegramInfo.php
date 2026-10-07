<?php

namespace App\Console\Commands;

use App\Services\TelegramBot;
use App\Services\TelegramException;
use Illuminate\Console\Command;

class TelegramInfo extends Command
{
    protected $signature = 'telegram:info';

    protected $aliases = ['telegram:webhook-info'];

    protected $description = 'Show the Telegram bot identity (getMe) and webhook status (getWebhookInfo)';

    public function handle(TelegramBot $bot): int
    {
        if (! $bot->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        try {
            $me = $bot->getMe();
            $webhook = $bot->getWebhookInfo();
        } catch (TelegramException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Field', 'Value'], [
            ['Bot', '@'.($me['username'] ?? '?').' ('.($me['first_name'] ?? '').', id '.($me['id'] ?? '?').')'],
            ['Owner chat id', config('services.telegram.chat_id') ?: '(not set)'],
            ['Webhook secret', filled(config('services.telegram.webhook_secret')) ? 'set' : '(not set)'],
            ['Webhook URL', ($webhook['url'] ?? '') ?: '(none: use telegram:poll)'],
            ['Pending updates', (string) ($webhook['pending_update_count'] ?? 0)],
            ['Last webhook error', isset($webhook['last_error_message'])
                ? $webhook['last_error_message'].' @ '.date('Y-m-d H:i:s', $webhook['last_error_date'] ?? 0)
                : '-'],
        ]);

        return self::SUCCESS;
    }
}
