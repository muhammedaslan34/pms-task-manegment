<?php

namespace App\Livewire\Admin;

use App\Services\McpTokenService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * In-app guide for connecting AI coding agents (Claude Code, Claude Desktop / claude.ai, Codex,
 * Cursor, Hermes Agent) to the tasks MCP server (routes/ai.php), plus management of the user's
 * personal access tokens (App\Services\McpTokenService).
 */
class McpGuide extends Component
{
    public string $tokenName = '';

    /** Plaintext of the token created in this request cycle; shown once, never persisted. */
    #[Locked]
    public ?string $plainToken = null;

    public function createToken(): void
    {
        $this->tokenName = trim($this->tokenName);

        $this->validate(
            ['tokenName' => ['required', 'string', 'max:100']],
            [],
            ['tokenName' => __('Token name')],
        );

        $this->plainToken = app(McpTokenService::class)->create(auth()->user(), $this->tokenName);
        $this->reset('tokenName');
        unset($this->tokens);
    }

    public function dismissToken(): void
    {
        $this->plainToken = null;
    }

    public function revokeToken(string $tokenId): void
    {
        // The service only touches the current user's own personal tokens.
        app(McpTokenService::class)->revoke(auth()->user(), $tokenId);
        unset($this->tokens);
    }

    /**
     * @return Collection<int, array{id: string, name: string|null, created_at: \Illuminate\Support\Carbon, last_used_at: \Illuminate\Support\Carbon|null, expires_at: \Illuminate\Support\Carbon|null}>
     */
    #[Computed]
    public function tokens(): Collection
    {
        return app(McpTokenService::class)->tokensFor(auth()->user());
    }

    public function render()
    {
        $url = url('/mcp/tasks');

        return view('livewire.admin.mcp-guide', [
            'mcpUrl' => $url,
            'isPublicHttps' => $this->isPublicHttps($url),
            'tools' => $this->tools(),
            'clients' => $this->clients($url),
        ])
            ->layout('components.layouts.app')
            ->title(__('AI agents (MCP)'));
    }

    protected function isPublicHttps(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && ! in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
            && ! str_ends_with($host, '.localhost');
    }

    /** @return list<array{name: string, kind: string, description: string}> */
    protected function tools(): array
    {
        return [
            ['name' => 'list_tasks', 'kind' => 'tool', 'description' => __('List open tasks, most urgent first, or filter by status, priority or text.')],
            ['name' => 'get_task', 'kind' => 'tool', 'description' => __('Read one task in full: description, page link and screenshots.')],
            ['name' => 'start_task', 'kind' => 'tool', 'description' => __('Mark a task as in progress so others can see it is being worked on.')],
            ['name' => 'complete_task', 'kind' => 'tool', 'description' => __('Mark a task as completed with a note describing what was done.')],
            ['name' => 'update_task_status', 'kind' => 'tool', 'description' => __('Change a task status, e.g. move it back to pending with an explanation.')],
            ['name' => 'implement_task', 'kind' => 'prompt', 'description' => __('Ready-made prompt: pick a task (or pass an id), implement it in your codebase and close it with a resolution note.')],
        ];
    }

    /**
     * Setup instructions per client. Each section is a list of steps; a step has a text and an
     * optional code block. Commands and configs follow the official docs linked in `docs`.
     *
     * @return list<array<string, mixed>>
     */
    protected function clients(string $url): array
    {
        $artisan = str_replace('\\', '/', base_path('artisan'));
        $tokenEnv = 'TASKS_MCP_TOKEN';

        return [
            [
                'key' => 'claude-code',
                'name' => 'Claude Code',
                'docs' => [['label' => __('Claude Code MCP docs'), 'url' => 'https://code.claude.com/docs/en/mcp']],
                'oauth' => [
                    ['text' => __('Add the server (available in the current project):'), 'code' => "claude mcp add --transport http tasks {$url}"],
                    ['text' => __('Or add it for all your projects:'), 'code' => "claude mcp add --transport http --scope user tasks {$url}"],
                    ['text' => __('Start Claude Code, run /mcp, select "tasks" and choose Authenticate. Your browser opens: sign in to TaskFlow and approve the access. You can also run:'), 'code' => 'claude mcp login tasks'],
                ],
                'token' => [
                    ['text' => __('Add the server with your token in the Authorization header:'), 'code' => "claude mcp add --transport http tasks {$url} --header \"Authorization: Bearer <your-token>\""],
                    ['text' => __('Or, for a shared project, commit a .mcp.json that reads the token from the TASKS_MCP_TOKEN environment variable:'), 'code' => $this->json(['mcpServers' => ['tasks' => [
                        'type' => 'http',
                        'url' => $url,
                        'headers' => ['Authorization' => 'Bearer ${'.$tokenEnv.'}'],
                    ]]])],
                ],
                'stdio' => [
                    ['text' => __('Run on the machine where this project is installed:'), 'code' => "claude mcp add --transport stdio tasks -- php {$artisan} mcp:start tasks"],
                ],
            ],
            [
                'key' => 'claude-desktop',
                'name' => 'Claude Desktop / claude.ai',
                'docs' => [
                    ['label' => __('Custom connectors docs'), 'url' => 'https://claude.com/docs/connectors/custom/add-unlisted'],
                    ['label' => __('Getting started with remote MCP'), 'url' => 'https://support.claude.com/en/articles/11175166-getting-started-with-custom-connectors-using-remote-mcp'],
                ],
                'notice' => __('Claude Desktop and claude.ai connect from Anthropic\'s cloud, so the server must be reachable on a public HTTPS URL.'),
                'oauth' => [
                    ['text' => __('Open Settings › Connectors, click "+ Add" and choose "Add custom connector".')],
                    ['text' => __('Enter the name "tasks" and this URL:'), 'code' => $url],
                    ['text' => __('For the OAuth client choose "Register automatically", then click Connect. Sign in to TaskFlow in the window that opens and approve the access.')],
                    ['text' => __('Team / Enterprise plans: an owner adds the connector under Organization settings › Connectors, then members connect it.')],
                ],
                'token' => [
                    ['text' => __('If your organization has request headers enabled for custom connectors (beta): choose "No sign-in" and add the request header authorization with the value:'), 'code' => 'Bearer <your-token>'],
                    ['text' => __('Otherwise, in Claude Desktop add this to claude_desktop_config.json (it uses mcp-remote, which needs Node.js):'), 'code' => $this->json(['mcpServers' => ['tasks' => [
                        'command' => 'npx',
                        'args' => ['mcp-remote', $url, '--header', 'Authorization:${AUTH_HEADER}'],
                        'env' => ['AUTH_HEADER' => 'Bearer <your-token>'],
                    ]]])],
                ],
                'stdio' => [
                    ['text' => __('Claude Desktop only. Edit claude_desktop_config.json (Windows: %APPDATA%\\Claude\\, macOS: ~/Library/Application Support/Claude/) and restart Claude Desktop:'), 'code' => $this->json(['mcpServers' => ['tasks' => [
                        'command' => 'php',
                        'args' => [$artisan, 'mcp:start', 'tasks'],
                    ]]])],
                ],
            ],
            [
                'key' => 'codex',
                'name' => 'Codex',
                'docs' => [['label' => __('Codex MCP docs'), 'url' => 'https://developers.openai.com/codex/mcp']],
                'oauth' => [
                    ['text' => __('Add the server:'), 'code' => "codex mcp add tasks --url {$url}"],
                    ['text' => __('Sign in. Your browser opens: sign in to TaskFlow and approve the access.'), 'code' => 'codex mcp login tasks --scopes mcp:use'],
                    ['text' => __('Equivalent entry in ~/.codex/config.toml:'), 'code' => "[mcp_servers.tasks]\nurl = \"{$url}\""],
                ],
                'token' => [
                    ['text' => __('Add the server and tell Codex which environment variable holds the token:'), 'code' => "codex mcp add tasks --url {$url} --bearer-token-env-var {$tokenEnv}"],
                    ['text' => __('Equivalent entry in ~/.codex/config.toml:'), 'code' => "[mcp_servers.tasks]\nurl = \"{$url}\"\nbearer_token_env_var = \"{$tokenEnv}\""],
                    ['text' => __('Set the variable before starting Codex (bash / zsh):'), 'code' => "export {$tokenEnv}=<your-token>"],
                    ['text' => __('Or in PowerShell:'), 'code' => "\$env:{$tokenEnv}=\"<your-token>\""],
                ],
                'stdio' => [
                    ['text' => __('Run on the machine where this project is installed:'), 'code' => "codex mcp add tasks -- php {$artisan} mcp:start tasks"],
                    ['text' => __('Equivalent entry in ~/.codex/config.toml:'), 'code' => "[mcp_servers.tasks]\ncommand = \"php\"\nargs = [\"{$artisan}\", \"mcp:start\", \"tasks\"]"],
                ],
            ],
            [
                'key' => 'cursor',
                'name' => 'Cursor',
                'docs' => [
                    ['label' => __('Cursor MCP docs'), 'url' => 'https://cursor.com/docs/context/mcp'],
                    ['label' => __('Cursor install links'), 'url' => 'https://cursor.com/docs/context/mcp/install-links'],
                ],
                'install' => 'cursor://anysphere.cursor-deeplink/mcp/install?name=tasks&config='
                    .rawurlencode(base64_encode(json_encode(['url' => $url], JSON_UNESCAPED_SLASHES))),
                'oauth' => [
                    ['text' => __('Click "Add to Cursor" above, or add this to ~/.cursor/mcp.json (all projects) or .cursor/mcp.json (this project):'), 'code' => $this->json(['mcpServers' => ['tasks' => ['url' => $url]]])],
                    ['text' => __('Open Cursor Settings › MCP. Cursor shows a login / connect button for "tasks": click it, sign in to TaskFlow and approve the access.')],
                ],
                'token' => [
                    ['text' => __('Use this entry instead; Cursor reads the token from the TASKS_MCP_TOKEN environment variable:'), 'code' => $this->json(['mcpServers' => ['tasks' => [
                        'url' => $url,
                        'headers' => ['Authorization' => 'Bearer ${env:'.$tokenEnv.'}'],
                    ]]])],
                ],
                'stdio' => [
                    ['text' => __('Run on the machine where this project is installed:'), 'code' => $this->json(['mcpServers' => ['tasks' => [
                        'command' => 'php',
                        'args' => [$artisan, 'mcp:start', 'tasks'],
                    ]]])],
                ],
            ],
            [
                'key' => 'hermes',
                'name' => 'Hermes Agent',
                'docs' => [['label' => __('Hermes Agent MCP docs'), 'url' => 'https://hermes-agent.nousresearch.com/docs/user-guide/features/mcp']],
                'oauth' => [
                    ['text' => __('Add the server:'), 'code' => "hermes mcp add tasks --url {$url} --auth oauth"],
                    ['text' => __('Or add it to ~/.hermes/config.yaml, then run /reload-mcp:'), 'code' => "mcp_servers:\n  tasks:\n    url: \"{$url}\"\n    auth: oauth"],
                    ['text' => __('Sign in and check the connection:'), 'code' => "hermes mcp login tasks\nhermes mcp test tasks"],
                ],
                'token' => [
                    ['text' => __('Add this to ~/.hermes/config.yaml:'), 'code' => "mcp_servers:\n  tasks:\n    url: \"{$url}\"\n    headers:\n      Authorization: \"Bearer \${{$tokenEnv}}\""],
                    ['text' => __('Put the token in ~/.hermes/.env, then run /reload-mcp:'), 'code' => "{$tokenEnv}=<your-token>"],
                ],
                'stdio' => [
                    ['text' => __('Run on the machine where this project is installed:'), 'code' => "hermes mcp add tasks --command php --args {$artisan} mcp:start tasks"],
                ],
            ],
        ];
    }

    /** Pretty-printed JSON with two-space indentation. */
    protected function json(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return preg_replace_callback('/^( +)/m', fn (array $m) => str_repeat(' ', intdiv(strlen($m[1]), 2)), $json);
    }
}
