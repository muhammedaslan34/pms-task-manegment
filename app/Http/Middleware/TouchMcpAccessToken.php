<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records oauth_access_tokens.last_used_at for the bearer token that authenticated the request.
 * Runs after auth:api; writes at most once a minute per token (single conditional UPDATE).
 */
class TouchMcpAccessToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof AccessToken && is_string($token->oauth_access_token_id ?? null)) {
            $now = now();

            Passport::token()->newQuery()
                ->whereKey($token->oauth_access_token_id)
                ->where(fn ($query) => $query
                    ->whereNull('last_used_at')
                    ->orWhere('last_used_at', '<', $now->copy()->subMinute()))
                ->toBase()
                ->update(['last_used_at' => $now]);
        }

        return $next($request);
    }
}
