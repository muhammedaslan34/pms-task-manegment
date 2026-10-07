<?php

namespace App\Providers;

use App\Services\TelegramBot;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TelegramBot::class, fn () => TelegramBot::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePassport();
        $this->configureRateLimiting();
    }

    /**
     * OAuth 2.1 server for the HTTP MCP endpoint (see routes/ai.php).
     */
    protected function configurePassport(): void
    {
        // Consent screen shown to the logged-in user when an MCP client (Claude Code, Codex, Cursor, ...)
        // starts the authorization-code flow.
        Passport::authorizationView(fn (array $parameters) => view('mcp.authorize', $parameters));

        // OAuth clients refresh their access token with the refresh token; personal access tokens
        // (McpTokenService) keep Passport's default lifetime of one year.
        Passport::tokensExpireIn(now()->addDays(7));
        Passport::refreshTokensExpireIn(now()->addDays(90));
    }

    protected function configureRateLimiting(): void
    {
        // Authenticated MCP JSON-RPC calls, per user.
        RateLimiter::for('mcp', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip()));

        // Unauthenticated OAuth discovery + dynamic client registration, per IP. Registration
        // writes a client row, so it is limited much more tightly than the metadata GETs.
        RateLimiter::for('mcp-oauth', fn (Request $request) => $request->isMethod('POST')
            ? Limit::perMinute(10)->by('register|'.$request->ip())
            : Limit::perMinute(120)->by('discovery|'.$request->ip()));
    }
}
