<?php

namespace App\Http\Controllers;

use App\Services\TelegramUpdateHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function Illuminate\Support\defer;

/**
 * Receives Telegram updates (set up with `php artisan telegram:set-webhook`).
 *
 * The request must carry the secret registered with setWebhook in the
 * X-Telegram-Bot-Api-Secret-Token header. Valid updates are acknowledged with
 * 200 immediately and processed after the response, so Telegram never retries
 * because of a slow or failing handler.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramUpdateHandler $handler): JsonResponse
    {
        $secret = (string) config('services.telegram.webhook_secret');
        $given = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($secret === '' || ! hash_equals($secret, $given)) {
            abort(403);
        }

        $update = $request->json()->all();

        if (is_array($update) && $update !== []) {
            defer(function () use ($handler, $update) {
                try {
                    $handler->handle($update);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        }

        return response()->json(['ok' => true]);
    }
}
