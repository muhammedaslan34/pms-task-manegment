<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use RuntimeException;

/**
 * Personal access tokens (static bearer tokens) for the HTTP MCP endpoint /mcp/tasks.
 *
 * For MCP clients without a working OAuth flow (Hermes Agent, CI scripts, Codex with
 * `bearer_token_env_var`, ...). The client sends `Authorization: Bearer <token>`; the tokens are
 * Passport personal access tokens (scope "mcp:use", default lifetime one year) and are accepted
 * by the `auth:api` guard exactly like OAuth access tokens.
 *
 * Interface:
 *
 *   tokensFor(User $user): Collection
 *       The user's active (not revoked, not expired) personal tokens, newest first. Each item is an
 *       array: ['id' => string, 'name' => string|null, 'created_at' => Carbon,
 *       'last_used_at' => ?Carbon, 'expires_at' => ?Carbon]. OAuth tokens issued to MCP clients
 *       through the authorization-code flow are not included.
 *
 *   create(User $user, string $name): string
 *       Creates a token and returns the plaintext bearer token. It is not stored anywhere and
 *       cannot be retrieved again, so show it to the user once.
 *
 *   revoke(User $user, string $tokenId): void
 *       Revokes one of the user's own personal tokens. Unknown ids and other users' tokens are
 *       ignored (no exception), so a user can never revoke someone else's token.
 *
 * The Passport "personal access" client that signs these tokens is created lazily on first use,
 * so a fresh deploy needs no extra step beyond `php artisan migrate` and the Passport keys.
 */
class McpTokenService
{
    public function __construct(
        protected ClientRepository $clients,
    ) {}

    /**
     * @return Collection<int, array{id: string, name: string|null, created_at: Carbon, last_used_at: Carbon|null, expires_at: Carbon|null}>
     */
    public function tokensFor(User $user): Collection
    {
        return $this->personalTokensQuery($user)
            ->where('revoked', false)
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->get()
            ->map(fn (Token $token): array => [
                'id' => (string) $token->getKey(),
                'name' => $token->name,
                'created_at' => $token->created_at,
                'last_used_at' => $token->last_used_at !== null ? Carbon::parse($token->last_used_at) : null,
                'expires_at' => $token->expires_at,
            ])
            ->values();
    }

    public function create(User $user, string $name): string
    {
        $this->ensurePersonalAccessClient();

        return $user->createToken($name, [Registrar::OAUTH_SCOPE])->accessToken;
    }

    public function revoke(User $user, string $tokenId): void
    {
        $this->personalTokensQuery($user)
            ->whereKey($tokenId)
            ->update(['revoked' => true]);
    }

    /**
     * Access tokens of the given user that were issued by a personal access client.
     *
     * @return Builder<Token>
     */
    protected function personalTokensQuery(User $user): Builder
    {
        // grant_types is a JSON array stored in a text column, e.g. ["personal_access"].
        $personalClientIds = Passport::client()->newQuery()
            ->where('grant_types', 'like', '%"personal_access"%')
            ->select('id');

        return Passport::token()->newQuery()
            ->where('user_id', $user->getAuthIdentifier())
            ->whereIn('client_id', $personalClientIds);
    }

    /**
     * Idempotently create the Passport personal access client for the users provider.
     */
    protected function ensurePersonalAccessClient(): void
    {
        $provider = config('auth.guards.api.provider');

        try {
            $this->clients->personalAccessClient($provider);
        } catch (RuntimeException) {
            $this->clients->createPersonalAccessGrantClient(config('app.name').' MCP personal tokens', $provider);
        }
    }
}
