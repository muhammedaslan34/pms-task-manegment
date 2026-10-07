<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin client for the Telegram Bot API (https://core.telegram.org/bots/api).
 *
 * Every method returns the decoded "result" field and throws TelegramException
 * when Telegram answers ok:false or the request fails. Exception messages are
 * scrubbed so the bot token (which is part of the URL) never ends up in logs.
 */
class TelegramBot
{
    /** Default HTTP timeout in seconds; uploads and long polling pass their own. */
    private const TIMEOUT = 15;

    private const UPLOAD_TIMEOUT = 60;

    public function __construct(
        private readonly ?string $token,
        private readonly string $apiUrl = 'https://api.telegram.org',
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            config('services.telegram.bot_token'),
            config('services.telegram.api_url') ?: 'https://api.telegram.org',
        );
    }

    public function isConfigured(): bool
    {
        return filled($this->token);
    }

    public function getMe(): array
    {
        return $this->call('getMe');
    }

    public function sendMessage(int|string $chatId, string $text, array $options = []): array
    {
        return $this->call('sendMessage', ['chat_id' => $chatId, 'text' => $text] + $options);
    }

    public function sendPhoto(int|string $chatId, string $bytes, string $filename, array $options = []): array
    {
        return $this->upload('sendPhoto', ['chat_id' => $chatId] + $options, ['photo' => [$bytes, $filename]]);
    }

    public function sendDocument(int|string $chatId, string $bytes, string $filename, array $options = []): array
    {
        return $this->upload('sendDocument', ['chat_id' => $chatId] + $options, ['document' => [$bytes, $filename]]);
    }

    /**
     * Send 2–10 files as an album. Telegram only allows photos/videos together
     * or documents together, never photos mixed with documents.
     *
     * @param  list<array{type: 'photo'|'document', bytes: string, filename: string, caption?: string, parse_mode?: string}>  $items
     */
    public function sendMediaGroup(int|string $chatId, array $items, array $options = []): array
    {
        $media = [];
        $files = [];

        foreach (array_values($items) as $index => $item) {
            $field = 'file'.$index;
            $files[$field] = [$item['bytes'], $item['filename']];
            $media[] = array_filter([
                'type' => $item['type'],
                'media' => 'attach://'.$field,
                'caption' => $item['caption'] ?? null,
                'parse_mode' => $item['parse_mode'] ?? null,
            ], fn ($value) => $value !== null);
        }

        return $this->upload('sendMediaGroup', ['chat_id' => $chatId, 'media' => $media] + $options, $files);
    }

    public function editMessageText(int|string $chatId, int $messageId, string $text, array $options = []): array|bool
    {
        return $this->call('editMessageText', ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => $text] + $options);
    }

    public function editMessageReplyMarkup(int|string $chatId, int $messageId, ?array $replyMarkup): array|bool
    {
        return $this->call('editMessageReplyMarkup', array_filter([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => $replyMarkup,
        ], fn ($value) => $value !== null));
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): bool
    {
        return (bool) $this->call('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert ?: null,
        ], fn ($value) => $value !== null));
    }

    /**
     * Long-poll for updates. The HTTP timeout is kept above Telegram's own
     * long-poll timeout so an idle poll returns [] instead of erroring.
     */
    public function getUpdates(?int $offset = null, int $timeout = 30, array $allowedUpdates = ['message', 'callback_query']): array
    {
        return $this->call('getUpdates', array_filter([
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => $allowedUpdates,
        ], fn ($value) => $value !== null), $timeout + 10);
    }

    public function setWebhook(string $url, ?string $secretToken = null, array $allowedUpdates = ['message', 'callback_query'], bool $dropPendingUpdates = false): bool
    {
        return (bool) $this->call('setWebhook', array_filter([
            'url' => $url,
            'secret_token' => $secretToken,
            'allowed_updates' => $allowedUpdates,
            'drop_pending_updates' => $dropPendingUpdates ?: null,
        ], fn ($value) => $value !== null));
    }

    public function deleteWebhook(bool $dropPendingUpdates = false): bool
    {
        return (bool) $this->call('deleteWebhook', $dropPendingUpdates ? ['drop_pending_updates' => true] : []);
    }

    public function getWebhookInfo(): array
    {
        return $this->call('getWebhookInfo');
    }

    public function setMyCommands(array $commands): bool
    {
        return (bool) $this->call('setMyCommands', ['commands' => $commands]);
    }

    /** JSON request; arrays such as reply_markup are encoded as nested JSON objects. */
    public function call(string $method, array $params = [], ?int $timeout = null): mixed
    {
        return $this->send($method, fn (PendingRequest $request) => $request
            ->timeout($timeout ?? self::TIMEOUT)
            ->asJson()
            ->post($this->endpoint($method), $params));
    }

    /**
     * multipart/form-data request with raw file bytes (needed because the
     * screenshots live in a private bucket Telegram cannot fetch from).
     *
     * @param  array<string, array{0: string, 1: string}>  $files  field => [bytes, filename]
     */
    public function upload(string $method, array $params, array $files): mixed
    {
        return $this->send($method, function (PendingRequest $request) use ($method, $params, $files) {
            $request->timeout(self::UPLOAD_TIMEOUT)->asMultipart();

            foreach ($files as $field => [$bytes, $filename]) {
                $request->attach($field, $bytes, $filename);
            }

            $fields = [];
            foreach ($params as $name => $value) {
                $fields[$name] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
            }

            return $request->post($this->endpoint($method), $fields);
        });
    }

    /** @param  \Closure(PendingRequest): Response  $request */
    private function send(string $method, \Closure $request): mixed
    {
        if (! $this->isConfigured()) {
            throw new TelegramException('Telegram bot token is not configured.', $method);
        }

        try {
            $response = $request(Http::connectTimeout(10));
        } catch (\Throwable $exception) {
            // Connection errors include the request URL, i.e. the token: scrub it and drop the original.
            throw new TelegramException(
                "Telegram {$method} request failed: ".$this->scrub($exception->getMessage()),
                $method,
            );
        }

        $body = $response->json();

        if (! is_array($body) || ($body['ok'] ?? false) !== true) {
            $description = is_array($body)
                ? ($body['description'] ?? 'unknown error')
                : 'HTTP '.$response->status().' with a non-JSON body';

            throw new TelegramException(
                "Telegram {$method} failed: ".$this->scrub((string) $description),
                $method,
                is_array($body) ? ($body['error_code'] ?? $response->status()) : $response->status(),
                is_array($body) ? ($body['parameters']['retry_after'] ?? null) : null,
            );
        }

        return $body['result'] ?? null;
    }

    private function endpoint(string $method): string
    {
        return rtrim($this->apiUrl, '/').'/bot'.$this->token.'/'.$method;
    }

    private function scrub(string $text): string
    {
        return filled($this->token) ? str_replace($this->token, '***', $text) : $text;
    }
}
