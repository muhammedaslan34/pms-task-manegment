<?php

namespace App\Console\Commands;

use App\Services\TaskTelegramNotifier;
use App\Services\TelegramBot;
use App\Services\TelegramException;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook
        {--url= : Public HTTPS base URL (e.g. a tunnel) or full webhook URL (default: APP_URL); /telegram/webhook is appended when no path is given}
        {--drop-pending : Discard updates Telegram queued while no webhook was set}';

    protected $description = 'Point the Telegram bot at this app\'s webhook (production delivery mode)';

    public function handle(TelegramBot $bot): int
    {
        if (! $bot->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        $secret = (string) config('services.telegram.webhook_secret');
        if ($secret === '') {
            $this->error('TELEGRAM_WEBHOOK_SECRET is not set (Telegram sends it back so the webhook can verify requests).');

            return self::FAILURE;
        }

        $url = $this->webhookUrl($this->option('url'));

        if (! TaskTelegramNotifier::isPublicUrl($url, requireHttps: true)) {
            $this->error("Telegram only delivers webhooks to public HTTPS URLs, got: {$url}");
            $this->line('Set APP_URL to your public https:// address, pass --url=..., or use `php artisan telegram:poll` locally.');

            return self::FAILURE;
        }

        try {
            $bot->setWebhook($url, $secret, ['message', 'callback_query'], (bool) $this->option('drop-pending'));
            $bot->setMyCommands(self::commands());
            $info = $bot->getWebhookInfo();
        } catch (TelegramException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Webhook set: {$info['url']}");
        $this->line('Pending updates: '.($info['pending_update_count'] ?? 0));

        return self::SUCCESS;
    }

    /** --url as given when it has a path, otherwise that host + the webhook route path. */
    private function webhookUrl(?string $url): string
    {
        $path = route('telegram.webhook', absolute: false);

        if (blank($url)) {
            return route('telegram.webhook');
        }

        $url = rtrim($url, '/');

        return in_array(parse_url($url, PHP_URL_PATH) ?? '', ['', '/'], true) ? $url.$path : $url;
    }

    /** The bot's command menu in Telegram clients. */
    public static function commands(): array
    {
        return [
            ['command' => 'tasks', 'description' => 'List open tasks'],
            ['command' => 'task', 'description' => 'Show a task: /task <id>'],
            ['command' => 'start', 'description' => 'Show this chat\'s id'],
        ];
    }
}
