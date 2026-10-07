<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Services\McpTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use phpseclib4\Crypt\RSA;
use Tests\TestCase;

class McpHttpAuthTest extends TestCase
{
    use RefreshDatabase;

    /** @var array{private: string, public: string}|null */
    private static ?array $keys = null;

    protected function setUp(): void
    {
        parent::setUp();

        // In-memory Passport signing keys so tests don't depend on storage/oauth-*.key.
        if (self::$keys === null) {
            $key = RSA::createKey(2048);
            self::$keys = ['private' => (string) $key, 'public' => (string) $key->getPublicKey()];
        }

        config([
            'passport.private_key' => self::$keys['private'],
            'passport.public_key' => self::$keys['public'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, string>  $headers
     */
    private function rpc(string $method, array $params = [], array $headers = [], int $id = 1): TestResponse
    {
        return $this->postJson('/mcp/tasks', [
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => (object) $params,
        ], ['Accept' => 'application/json, text/event-stream', ...$headers]);
    }

    /** @param  array<string, string>  $headers */
    private function initialize(array $headers = []): TestResponse
    {
        return $this->rpc('initialize', [
            'protocolVersion' => '2025-06-18',
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'phpunit', 'version' => '1.0'],
        ], $headers);
    }

    /** @return array<int, string> */
    private function toolNames(TestResponse $response): array
    {
        return collect($response->json('result.tools'))->pluck('name')->sort()->values()->all();
    }

    public function test_unauthenticated_request_gets_401_with_resource_metadata_challenge(): void
    {
        $response = $this->initialize()->assertUnauthorized();

        $this->assertSame(
            'Bearer realm="mcp", resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp/tasks').'"',
            $response->headers->get('WWW-Authenticate'),
        );

        // No JSON Accept header (plain curl): still a 401, never a redirect to /login.
        $this->call('POST', '/mcp/tasks', server: ['CONTENT_TYPE' => 'application/json'], content: '{}')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate');
    }

    public function test_invalid_bearer_token_is_rejected(): void
    {
        $this->initialize(['Authorization' => 'Bearer not-a-real-token'])->assertUnauthorized();
    }

    public function test_discovery_endpoints_describe_the_resource_and_authorization_server(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource/mcp/tasks')
            ->assertOk()
            ->assertExactJson([
                'resource' => url('/mcp/tasks'),
                'authorization_servers' => [url('/')],
                'scopes_supported' => ['mcp:use'],
            ]);

        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJson([
                'issuer' => url('/'),
                'authorization_endpoint' => url('/oauth/authorize'),
                'token_endpoint' => url('/oauth/token'),
                'registration_endpoint' => url('/oauth/register'),
                'code_challenge_methods_supported' => ['S256'],
                'grant_types_supported' => ['authorization_code', 'refresh_token'],
                'scopes_supported' => ['mcp:use'],
            ]);
    }

    public function test_discovery_urls_follow_the_forwarded_https_host_of_the_proxy(): void
    {
        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'tasks.example.com',
            'X-Forwarded-Port' => '443',
        ])->getJson('/.well-known/oauth-protected-resource/mcp/tasks')
            ->assertOk()
            ->assertJsonPath('resource', 'https://tasks.example.com/mcp/tasks')
            ->assertJsonPath('authorization_servers.0', 'https://tasks.example.com');
    }

    public function test_dynamic_client_registration_returns_a_public_client(): void
    {
        $response = $this->postJson('/oauth/register', [
            'client_name' => 'Claude Code',
            'redirect_uris' => ['http://localhost:54321/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'none',
        ])->assertCreated()
            ->assertJsonPath('token_endpoint_auth_method', 'none')
            ->assertJsonPath('scope', 'mcp:use')
            ->assertJsonPath('redirect_uris', ['http://localhost:54321/callback']);

        $client = Client::findOrFail($response->json('client_id'));
        $this->assertSame('Claude Code', $client->name);
        $this->assertFalse($client->confidential());
    }

    public function test_cursor_registration_with_its_three_redirect_uris_is_accepted(): void
    {
        // Cursor 3.14.x registers all three in one request; every URI must pass validation.
        $redirectUris = [
            'cursor://anysphere.cursor-mcp/oauth/callback',
            'https://www.cursor.com/agents/mcp/oauth/callback',
            'http://localhost:8787/callback',
        ];

        $response = $this->postJson('/oauth/register', [
            'client_name' => 'Cursor',
            'redirect_uris' => $redirectUris,
        ])->assertCreated()
            ->assertJsonPath('redirect_uris', $redirectUris);

        $this->assertSame($redirectUris, Client::findOrFail($response->json('client_id'))->redirect_uris);
    }

    public function test_registration_accepts_the_callbacks_of_other_supported_clients(): void
    {
        $cases = [
            'Claude Code' => ['http://localhost:51234/callback'],
            'Codex' => ['http://127.0.0.1:43123/callback'],
            'Hermes' => ['http://127.0.0.1:9876/callback'],
            'Claude' => ['https://claude.ai/api/mcp/auth_callback'],
            'mcp-remote' => ['http://localhost:3334/oauth/callback'],
        ];

        foreach ($cases as $name => $redirectUris) {
            $this->postJson('/oauth/register', ['client_name' => $name, 'redirect_uris' => $redirectUris])
                ->assertCreated()
                ->assertJsonPath('redirect_uris', $redirectUris);
        }
    }

    public function test_registration_rejects_unknown_redirect_domains(): void
    {
        $this->postJson('/oauth/register', [
            'client_name' => 'Evil',
            'redirect_uris' => ['https://evil.example.com/callback'],
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_redirect_uri');

        $this->assertSame(0, Client::count());
    }

    public function test_authenticated_client_can_initialize_and_list_and_call_tools_over_http(): void
    {
        Task::factory()->create(['title' => 'Broken checkout button', 'status' => TaskStatus::Pending]);
        Passport::actingAs(User::factory()->create(), ['mcp:use']);

        $this->initialize()
            ->assertOk()
            ->assertJsonPath('result.serverInfo.name', 'Task Management')
            ->assertJsonPath('result.protocolVersion', '2025-06-18');

        $tools = $this->rpc('tools/list', id: 2)->assertOk();
        $this->assertSame(
            ['complete_task', 'get_task', 'list_tasks', 'start_task', 'update_task_status'],
            $this->toolNames($tools),
        );

        $this->rpc('tools/call', ['name' => 'list_tasks', 'arguments' => (object) []], id: 3)
            ->assertOk()
            ->assertSee('Broken checkout button');
    }

    public function test_personal_access_token_lifecycle(): void
    {
        $user = User::factory()->create();
        $service = app(McpTokenService::class);

        $this->assertCount(0, $service->tokensFor($user));

        $plain = $service->create($user, 'Hermes agent');
        $this->assertNotEmpty($plain);

        // A second token reuses the lazily created personal access client.
        $service->create($user, 'CI');
        $this->assertSame(1, Client::query()->where('grant_types', 'like', '%personal_access%')->count());

        $tokens = $service->tokensFor($user);
        $this->assertCount(2, $tokens);
        $this->assertEqualsCanonicalizing(['Hermes agent', 'CI'], $tokens->pluck('name')->all());
        $this->assertSame(['id', 'name', 'created_at', 'last_used_at', 'expires_at'], array_keys($tokens->first()));
        $this->assertTrue($tokens->first()['expires_at']->isFuture());

        $hermes = $tokens->firstWhere('name', 'Hermes agent');
        $this->assertNull($hermes['last_used_at']);

        // The plaintext token authenticates /mcp/tasks and records last use.
        $bearer = ['Authorization' => 'Bearer '.$plain];
        $this->initialize($bearer)->assertOk();
        $this->assertNotNull($service->tokensFor($user)->firstWhere('name', 'Hermes agent')['last_used_at']);
        $this->assertCount(5, $this->rpc('tools/list', [], $bearer, 2)->assertOk()->json('result.tools'));

        // Revoked tokens disappear from the list and are rejected.
        $service->revoke($user, $hermes['id']);
        $this->assertSame(['CI'], $service->tokensFor($user)->pluck('name')->all());

        $this->app['auth']->forgetGuards();
        $this->initialize($bearer)->assertUnauthorized();
    }

    public function test_user_cannot_revoke_another_users_token(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $service = app(McpTokenService::class);

        $plain = $service->create($owner, 'Owner token');
        $tokenId = $service->tokensFor($owner)->first()['id'];

        $service->revoke($other, $tokenId);
        $service->revoke($other, 'does-not-exist');

        $this->assertCount(1, $service->tokensFor($owner));
        $this->assertCount(0, $service->tokensFor($other));
        $this->initialize(['Authorization' => 'Bearer '.$plain])->assertOk();
    }

    public function test_oauth_access_tokens_are_not_listed_as_personal_tokens(): void
    {
        $user = User::factory()->create();
        $client = app(ClientRepository::class)
            ->createAuthorizationCodeGrantClient('Cursor', ['http://localhost/cb'], confidential: false);

        Token::forceCreate([
            'id' => Str::random(80),
            'user_id' => $user->id,
            'client_id' => $client->id,
            'name' => null,
            'scopes' => ['mcp:use'],
            'revoked' => false,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertCount(0, app(McpTokenService::class)->tokensFor($user));
    }

    public function test_authorize_requires_login_and_full_authorization_code_pkce_flow_works(): void
    {
        $redirectUri = 'http://127.0.0.1:43123/callback';
        $clientId = $this->postJson('/oauth/register', [
            'client_name' => 'Codex',
            'redirect_uris' => [$redirectUri],
        ])->assertCreated()->json('client_id');

        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'mcp:use',
            'state' => 'xyz',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'resource' => url('/mcp/tasks'),
        ]);

        // Guests are sent to the existing login page first.
        $this->get('/oauth/authorize?'.$query)->assertRedirect(route('login'));

        $user = User::factory()->create(['email' => 'agent-owner@example.com']);
        $this->actingAs($user, 'web');

        $this->withSession(['locale' => 'en'])->get('/oauth/authorize?'.$query)
            ->assertOk()
            ->assertViewIs('mcp.authorize')
            ->assertSee('Authorize Codex')
            ->assertSee('agent-owner@example.com');

        $approve = $this->withSession([
            'authToken' => session('authToken'),
            'authRequest' => session('authRequest'),
        ])->post('/oauth/authorize', ['auth_token' => session('authToken'), 'client_id' => $clientId]);

        $location = $approve->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith($redirectUri.'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $params);
        $this->assertSame('xyz', $params['state']);

        // Token endpoint takes form-urlencoded parameters (public client: no secret, PKCE verifier).
        $token = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'code' => $params['code'],
            'code_verifier' => $verifier,
            'resource' => url('/mcp/tasks'),
        ])->assertOk()->json();

        $this->assertArrayHasKey('refresh_token', $token);

        $this->app['auth']->forgetGuards();
        $this->rpc('tools/list', [], ['Authorization' => 'Bearer '.$token['access_token']])
            ->assertOk()
            ->assertJsonCount(5, 'result.tools');

        // The OAuth token is not a personal token.
        $this->assertCount(0, app(McpTokenService::class)->tokensFor($user));
    }

    public function test_consent_screen_is_translated_to_arabic(): void
    {
        $clientId = $this->postJson('/oauth/register', [
            'client_name' => 'Cursor',
            'redirect_uris' => ['http://localhost:8787/callback'],
        ])->json('client_id');

        $this->actingAs(User::factory()->create(), 'web');

        $this->withSession(['locale' => 'ar'])->get('/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => 'http://localhost:8787/callback',
            'scope' => 'mcp:use',
            'code_challenge' => str_repeat('a', 43),
            'code_challenge_method' => 'S256',
        ]))->assertOk()->assertSee('تفويض Cursor')->assertSee('dir="rtl"', false);
    }
}
