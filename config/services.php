<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        // The owner's chat: receives notifications and is the only chat allowed to press the buttons.
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        // Sent by Telegram in the X-Telegram-Bot-Api-Secret-Token header on every webhook call.
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        // Language of the bot's messages (labels come from lang/*.json).
        'locale' => env('TELEGRAM_LOCALE', 'en'),
        'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
    ],

];
