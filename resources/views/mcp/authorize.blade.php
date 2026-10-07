{{--
    OAuth consent screen for MCP clients (Claude Code, Codex, Cursor, Claude Desktop, ...).
    Rendered by Passport's AuthorizationController (registered in AppServiceProvider via
    Passport::authorizationView) with: $client, $user, $scopes, $request, $authToken.
--}}
<x-layouts.app>
    <div class="mx-auto flex max-w-md flex-col justify-center py-10">
        <div class="text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-primary/10">
                @if ($client->logo_uri ?? null)
                    <img src="{{ $client->logo_uri }}" alt="" class="h-9 w-9 rounded object-contain">
                @else
                    <svg class="h-7 w-7 text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                @endif
            </span>
            <h1 class="mt-4 font-display text-2xl font-bold text-brand">
                {{ __('Authorize :client', ['client' => $client->name]) }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ __(':client wants to access TaskFlow on your behalf through MCP.', ['client' => $client->name]) }}
            </p>
            @if ($client->client_uri ?? null)
                <a href="{{ $client->client_uri }}" target="_blank" rel="noopener noreferrer"
                    class="mt-1 inline-block text-sm text-blue-600 hover:underline" dir="ltr">{{ $client->client_uri }}</a>
            @endif
        </div>

        <div class="mt-8 space-y-6 rounded-2xl border border-slate-200/80 bg-white p-7 shadow-sm">
            <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                {{ __('Signed in as :email', ['email' => $user->email]) }}
            </div>

            <div>
                <p class="text-sm font-medium text-slate-700">{{ __('This application will be able to:') }}</p>
                <ul class="mt-3 space-y-2.5 text-sm text-slate-600">
                    @foreach ($scopes as $scope)
                        <li class="flex items-start gap-2.5">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600"></span>
                            <span>{{ __($scope->description) }}</span>
                        </li>
                    @endforeach
                    <li class="flex items-start gap-2.5">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600"></span>
                        <span>{{ __('List and read tasks, including their screenshots.') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600"></span>
                        <span>{{ __('Start and complete tasks and change their status in your name.') }}</span>
                    </li>
                </ul>
            </div>

            <p class="text-xs text-slate-500">
                {{ __('Only approve this if you just connected an AI agent or MCP client yourself.') }}
            </p>

            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="state" value="{{ $request->state }}">
                    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                    <button type="submit"
                        class="w-full rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        {{ __('Cancel') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="flex-1"
                    onsubmit="this.querySelector('button').disabled = true">
                    @csrf
                    <input type="hidden" name="state" value="{{ $request->state }}">
                    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                    <button type="submit"
                        class="w-full rounded-lg bg-blue-800 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900 focus:ring-2 focus:ring-blue-800 focus:ring-offset-2 disabled:opacity-50">
                        {{ __('Authorize') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
