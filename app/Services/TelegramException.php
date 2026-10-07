<?php

namespace App\Services;

use RuntimeException;

/**
 * A failed Telegram Bot API call. Messages never contain the bot token.
 */
class TelegramException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $method = '',
        public readonly ?int $errorCode = null,
        public readonly ?int $retryAfter = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $errorCode ?? 0, $previous);
    }

    /** Telegram's "message is not modified" error when an edit changes nothing. */
    public function isNotModified(): bool
    {
        return str_contains($this->getMessage(), 'message is not modified');
    }
}
