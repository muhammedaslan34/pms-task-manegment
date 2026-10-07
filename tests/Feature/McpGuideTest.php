<?php

namespace Tests\Feature;

use App\Livewire\Admin\McpGuide;
use App\Models\User;
use App\Services\McpTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use phpseclib4\Crypt\RSA;
use Tests\TestCase;

class McpGuideTest extends TestCase
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

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/mcp')->assertRedirect(route('login'));
    }

    public function test_logged_in_user_sees_the_guide(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.mcp'))
            ->assertOk()
            ->assertSee(url('/mcp/tasks'))
            ->assertSee('Claude Code')
            ->assertSee('Claude Desktop / claude.ai')
            ->assertSee('Codex')
            ->assertSee('Cursor')
            ->assertSee('Hermes Agent')
            ->assertSee('cursor://anysphere.cursor-deeplink/mcp/install?name=tasks&amp;config=', false)
            ->assertSee(route('admin.mcp'));
    }

    public function test_creating_a_token_shows_it_once_and_lists_it(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(McpGuide::class)
            ->set('tokenName', 'Laptop Hermes')
            ->call('createToken')
            ->assertHasNoErrors()
            ->assertSet('tokenName', '');

        $plain = $component->get('plainToken');
        $this->assertIsString($plain);
        $this->assertNotSame('', $plain);
        $component->assertSee($plain)->assertSee('Laptop Hermes');

        $tokens = app(McpTokenService::class)->tokensFor($user);
        $this->assertCount(1, $tokens);
        $this->assertSame('Laptop Hermes', $tokens->first()['name']);

        // Dismissing removes the plaintext; it cannot be shown again.
        $component->call('dismissToken')
            ->assertSet('plainToken', null)
            ->assertDontSee($plain)
            ->assertSee('Laptop Hermes');
    }

    public function test_token_name_is_required_and_limited(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(McpGuide::class)
            ->set('tokenName', '   ')
            ->call('createToken')
            ->assertHasErrors(['tokenName' => 'required'])
            ->set('tokenName', str_repeat('a', 101))
            ->call('createToken')
            ->assertHasErrors(['tokenName' => 'max'])
            ->assertSet('plainToken', null);

        $this->assertCount(0, app(McpTokenService::class)->tokensFor($user));
    }

    public function test_plain_token_cannot_be_set_from_the_client(): void
    {
        $user = User::factory()->create();

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($user)
            ->test(McpGuide::class)
            ->set('plainToken', 'injected');
    }

    public function test_revoking_removes_the_token(): void
    {
        $user = User::factory()->create();
        $service = app(McpTokenService::class);
        $service->create($user, 'Build server CI');
        $tokenId = $service->tokensFor($user)->first()['id'];

        Livewire::actingAs($user)
            ->test(McpGuide::class)
            ->assertSee('Build server CI')
            ->call('revokeToken', $tokenId)
            ->assertSee(__('You have no active personal access tokens.'));

        $this->assertCount(0, $service->tokensFor($user));
    }

    public function test_user_cannot_revoke_another_users_token(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $service = app(McpTokenService::class);
        $service->create($owner, 'Owner token');
        $tokenId = $service->tokensFor($owner)->first()['id'];

        Livewire::actingAs($attacker)
            ->test(McpGuide::class)
            ->assertDontSee('Owner token')
            ->call('revokeToken', $tokenId);

        $this->assertCount(1, $service->tokensFor($owner));
    }
}
