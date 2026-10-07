<?php

use App\Http\Middleware\TouchMcpAccessToken;
use App\Mcp\Servers\TasksServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

// Local (stdio) server for AI coding agents on this machine: `php artisan mcp:start tasks`.
Mcp::local('tasks', TasksServer::class);

// OAuth 2.1 discovery + dynamic client registration for remote MCP clients (backed by Passport):
//   GET  /.well-known/oauth-protected-resource[/{path}]
//   GET  /.well-known/oauth-authorization-server[/{path}]
//   POST /oauth/register
// Passport itself serves /oauth/authorize (consent screen: resources/views/mcp/authorize.blade.php)
// and /oauth/token.
Route::middleware('throttle:mcp-oauth')->group(function () {
    Mcp::oauthRoutes();
});

// Remote (streamable HTTP) server. Accepts OAuth access tokens issued through the flow above and
// personal access tokens created with App\Services\McpTokenService ("Authorization: Bearer <token>").
// Unauthenticated requests get a 401 whose WWW-Authenticate header points at the resource metadata.
Mcp::web('/mcp/tasks', TasksServer::class)
    ->middleware(['auth:api', TouchMcpAccessToken::class, 'throttle:mcp']);
