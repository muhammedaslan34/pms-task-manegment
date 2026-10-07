<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The app runs behind a reverse proxy (nginx on xCloud, cloudflared tunnel in dev). Trust its
        // X-Forwarded-* headers so generated URLs (OAuth/MCP discovery metadata, redirects) use the
        // public https scheme and host. "*" is fine as long as PHP is only reachable through that proxy.
        // (Not env-driven: this callback runs before .env is loaded, so env() here can't see .env values.)
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // MCP endpoints are API-only: always answer with JSON (e.g. a 401 instead of a redirect to /login),
        // whatever Accept header the client sends.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('mcp', 'mcp/*', 'oauth/register') || $request->expectsJson(),
        );
    })->create();
