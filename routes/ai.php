<?php

use App\Mcp\Servers\TasksServer;
use Laravel\Mcp\Facades\Mcp;

// Local (stdio) server for AI coding agents: `php artisan mcp:start tasks`.
// Intentionally no Mcp::web() endpoint: the tools change task status, so an
// HTTP endpoint would need authentication (e.g. a bearer-token middleware).
Mcp::local('tasks', TasksServer::class);
