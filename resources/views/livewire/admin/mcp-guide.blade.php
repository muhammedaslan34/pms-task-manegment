<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="font-display text-2xl font-bold text-brand">{{ __('AI agents (MCP)') }}</h1>
        <p class="mt-1 max-w-3xl text-sm leading-relaxed text-slate-500">
            {{ __('The Model Context Protocol (MCP) lets AI coding agents such as Claude Code, Codex or Cursor work with TaskFlow directly: they can read the submitted tasks, implement them in your codebase and close them, all in your name.') }}
        </p>
    </div>

    @unless ($isPublicHttps)
        <div class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <div class="min-w-0">
                <p class="font-semibold">{{ __('This server address is not a public HTTPS URL.') }}</p>
                <p class="mt-1 leading-relaxed">{{ __('Agents running on this same computer can use it, but remote clients such as claude.ai, Claude Desktop connectors or agents on other machines cannot. Open this page on the deployed HTTPS domain to get the address they need.') }}</p>
            </div>
        </div>
    @endunless

    {{-- Server URL + capabilities --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        <section class="min-w-0 rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-sm lg:col-span-2">
            <h2 class="font-display text-lg font-semibold text-brand">{{ __('Server URL') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Use this address when a client asks for the MCP server URL.') }}</p>
            <x-mcp.code class="mt-4" variant="light" :code="$mcpUrl" />
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <dt class="text-slate-500">{{ __('Transport') }}:</dt>
                    <dd class="font-medium text-slate-700">Streamable HTTP</dd>
                </div>
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <dt class="text-slate-500">{{ __('Server name') }}:</dt>
                    <dd><code dir="ltr" class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-700">tasks</code></dd>
                </div>
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <dt class="text-slate-500">{{ __('OAuth scope') }}:</dt>
                    <dd><code dir="ltr" class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-700">mcp:use</code></dd>
                </div>
            </dl>
        </section>

        <section class="min-w-0 rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-sm lg:col-span-3">
            <h2 class="font-display text-lg font-semibold text-brand">{{ __('What your agent can do') }}</h2>
            <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($tools as $tool)
                    <li class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <code dir="ltr" class="break-all font-mono text-[13px] font-semibold text-blue-800">{{ $tool['name'] }}</code>
                            @if ($tool['kind'] === 'prompt')
                                <span class="rounded bg-violet-100 px-1.5 py-0.5 text-[11px] font-semibold text-violet-700">{{ __('Prompt') }}</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ $tool['description'] }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- Connection methods --}}
    <section class="rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-sm">
        <h2 class="font-display text-lg font-semibold text-brand">{{ __('How to connect') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('There are three ways to connect. Pick the first one your client supports.') }}</p>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="min-w-0 rounded-xl border-2 border-blue-200 bg-blue-50/60 p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-display font-semibold text-slate-900">{{ __('OAuth sign-in') }}</h3>
                    <span class="rounded-full bg-blue-800 px-2 py-0.5 text-[11px] font-semibold text-white">{{ __('Recommended') }}</span>
                </div>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('The client opens your browser, you sign in to TaskFlow and approve the access. Nothing to copy or store, and access is renewed automatically.') }}</p>
            </div>
            <div class="min-w-0 rounded-xl border border-slate-200 bg-white p-4">
                <h3 class="font-display font-semibold text-slate-900">{{ __('Personal access token') }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('For clients or environments without OAuth support, such as CI jobs or headless servers. Create a token below; the client sends it in this header:') }}</p>
                <x-mcp.code class="mt-3" variant="light" code="Authorization: Bearer <token>" />
            </div>
            <div class="min-w-0 rounded-xl border border-slate-200 bg-white p-4">
                <h3 class="font-display font-semibold text-slate-900">{{ __('Local stdio') }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('Only on the machine where this project runs. The agent starts the server itself and works directly with that installation\'s database, without signing in.') }}</p>
                <x-mcp.code class="mt-3" variant="light" code="php artisan mcp:start tasks" />
            </div>
        </div>
    </section>

    {{-- Client setup tabs --}}
    <section class="rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-sm"
        x-data="{
            tab: @js($clients[0]['key']),
            keys: @js(array_column($clients, 'key')),
            init() {
                try {
                    const saved = window.localStorage.getItem('mcp-guide-tab');
                    if (saved && this.keys.includes(saved)) this.tab = saved;
                } catch (e) {}
            },
            select(key) {
                this.tab = key;
                try { window.localStorage.setItem('mcp-guide-tab', key); } catch (e) {}
            },
        }">
        <h2 class="font-display text-lg font-semibold text-brand">{{ __('Set up your client') }}</h2>

        <div class="mt-4 flex flex-wrap gap-2" role="tablist" aria-label="{{ __('MCP clients') }}">
            @foreach ($clients as $client)
                <button type="button" role="tab"
                    id="mcp-tab-{{ $client['key'] }}"
                    aria-controls="mcp-panel-{{ $client['key'] }}"
                    x-on:click="select(@js($client['key']))"
                    x-bind:aria-selected="tab === @js($client['key'])"
                    x-bind:class="tab === @js($client['key'])
                        ? 'border-blue-800 bg-blue-800 text-white shadow-sm'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                    class="rounded-lg border px-3.5 py-2 text-sm font-semibold transition">
                    {{ $client['name'] }}
                </button>
            @endforeach
        </div>

        @foreach ($clients as $client)
            <div role="tabpanel" id="mcp-panel-{{ $client['key'] }}" aria-labelledby="mcp-tab-{{ $client['key'] }}"
                x-show="tab === @js($client['key'])" @unless ($loop->first) x-cloak @endunless
                class="mt-5 space-y-6 border-t border-slate-100 pt-5">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h3 class="font-display text-base font-semibold text-slate-900">{{ $client['name'] }}</h3>
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                            @foreach ($client['docs'] as $doc)
                                <a href="{{ $doc['url'] }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 font-medium text-blue-700 hover:text-blue-900 hover:underline">
                                    {{ $doc['label'] }}
                                    <svg class="h-3.5 w-3.5 rtl:-scale-x-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h5a.75.75 0 010 1.5h-5zm7.25-.75a.75.75 0 01.75-.75h3.5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0V6.56l-5.22 5.22a.75.75 0 11-1.06-1.06l5.22-5.22h-1.69a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @isset($client['install'])
                        <a href="{{ $client['install'] }}"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                            </svg>
                            {{ __('Add to Cursor') }}
                        </a>
                    @endisset
                </div>

                @isset($client['notice'])
                    <p class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm leading-relaxed text-blue-900">{{ $client['notice'] }}</p>
                @endisset

                <div>
                    <h4 class="mb-3 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-900">
                        {{ __('Sign in with OAuth') }}
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-800">{{ __('Recommended') }}</span>
                    </h4>
                    <x-mcp.steps :steps="$client['oauth']" />
                </div>

                <div>
                    <h4 class="mb-1 text-sm font-semibold text-slate-900">{{ __('Using a personal token instead') }}</h4>
                    <p class="mb-3 text-sm text-slate-500">
                        {{ __('Create a token under Personal access tokens below and use it in place of <your-token>.') }}
                        <a href="#tokens" class="font-medium text-blue-700 hover:underline">{{ __('Go to tokens') }}</a>
                    </p>
                    <x-mcp.steps :steps="$client['token']" />
                </div>

                <details class="group rounded-xl border border-slate-200 bg-slate-50/60">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-slate-800 [&::-webkit-details-marker]:hidden">
                        <span>{{ __('Local stdio (this machine only)') }}</span>
                        <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </summary>
                    <div class="space-y-3 border-t border-slate-200 px-4 py-4">
                        <p class="text-sm leading-relaxed text-slate-500">{{ __('Requires PHP and this project on the same machine as the agent. The agent works with that installation\'s database.') }}</p>
                        <x-mcp.steps :steps="$client['stdio']" />
                    </div>
                </details>
            </div>
        @endforeach
    </section>

    {{-- Personal access tokens --}}
    <section id="tokens" class="scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-sm">
        <h2 class="font-display text-lg font-semibold text-brand">{{ __('Personal access tokens') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Tokens let a client connect without the OAuth sign-in. Anyone with a token can act in your name, so keep it secret and revoke tokens you no longer use. Tokens expire after one year.') }}</p>

        @if ($plainToken)
            <div class="mt-5 rounded-xl border border-amber-300 bg-amber-50/70 p-4" wire:key="new-token">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-amber-950">{{ __('Your new token') }}</p>
                        <p class="mt-1 text-sm text-amber-900">{{ __('Copy it now. For your security it will not be shown again.') }}</p>
                    </div>
                    <button type="button" wire:click="dismissToken"
                        class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">
                        {{ __('Done') }}
                    </button>
                </div>
                <x-mcp.code class="mt-3" variant="highlight" wrap :code="$plainToken" />
                <p class="mt-2 text-xs text-amber-900">
                    {{ __('Clients send it as') }}
                    <code dir="ltr" class="rounded bg-white/70 px-1 font-mono">Authorization: Bearer &lt;token&gt;</code>
                </p>
            </div>
        @endif

        <form wire:submit="createToken" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-start">
            <div class="min-w-0 flex-1 sm:max-w-md">
                <label for="token-name" class="sr-only">{{ __('Token name') }}</label>
                <input id="token-name" type="text" wire:model="tokenName" maxlength="100" autocomplete="off"
                    placeholder="{{ __('Token name, e.g. “Laptop Hermes” or “CI”') }}"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                @error('tokenName')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="createToken"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-900 disabled:opacity-60">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                </svg>
                {{ __('Create token') }}
            </button>
        </form>

        <div class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="hidden grid-cols-12 gap-3 bg-slate-50 px-4 py-2.5 text-xs font-semibold text-slate-500 md:grid">
                <div class="col-span-4">{{ __('Name') }}</div>
                <div class="col-span-2">{{ __('Created') }}</div>
                <div class="col-span-2">{{ __('Last used') }}</div>
                <div class="col-span-2">{{ __('Expires') }}</div>
                <div class="col-span-2 text-end">{{ __('Actions') }}</div>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($this->tokens as $token)
                    <li wire:key="token-{{ $token['id'] }}" class="grid grid-cols-2 gap-x-3 gap-y-1.5 px-4 py-3 md:grid-cols-12 md:items-center">
                        <div class="col-span-2 min-w-0 break-words font-medium text-slate-900 md:col-span-4">{{ $token['name'] ?: __('Unnamed token') }}</div>
                        <div class="text-slate-500 md:col-span-2">
                            <span class="text-xs text-slate-400 md:hidden">{{ __('Created') }}:</span>
                            {{ $token['created_at']->format('Y/m/d') }}
                        </div>
                        <div class="text-slate-500 md:col-span-2">
                            <span class="text-xs text-slate-400 md:hidden">{{ __('Last used') }}:</span>
                            {{ $token['last_used_at'] ? $token['last_used_at']->diffForHumans() : __('Never') }}
                        </div>
                        <div class="text-slate-500 md:col-span-2">
                            <span class="text-xs text-slate-400 md:hidden">{{ __('Expires') }}:</span>
                            {{ $token['expires_at'] ? $token['expires_at']->format('Y/m/d') : __('Never') }}
                        </div>
                        <div class="flex justify-end md:col-span-2">
                            <button type="button" wire:click="revokeToken(@js($token['id']))"
                                wire:confirm="{{ __('Revoke this token? Clients using it will lose access immediately.') }}"
                                class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100">
                                {{ __('Revoke') }}
                            </button>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-10 text-center text-sm text-slate-500">{{ __('You have no active personal access tokens.') }}</li>
                @endforelse
            </ul>
        </div>
    </section>

    {{-- Troubleshooting --}}
    <section class="rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-sm">
        <h2 class="font-display text-lg font-semibold text-brand">{{ __('Troubleshooting') }}</h2>
        <dl class="mt-4 divide-y divide-slate-100">
            <div class="py-3 first:pt-0">
                <dt class="text-sm font-semibold text-slate-900">{{ __('The client reports 401 Unauthorized or asks to sign in again') }}</dt>
                <dd class="mt-1 text-sm leading-relaxed text-slate-600">{{ __('The access was revoked or has expired. With OAuth, authenticate again from the client. With a personal token, check that it is still listed above; if not, create a new one and update the client.') }}</dd>
            </div>
            <div class="py-3">
                <dt class="text-sm font-semibold text-slate-900">{{ __('How do I reconnect?') }}</dt>
                <dd class="mt-1 text-sm leading-relaxed text-slate-600">{{ __('In Claude Code run /mcp, select "tasks" and authenticate or reconnect. In Cursor open Settings › MCP and click the connect button of "tasks" again. In Hermes run /reload-mcp. After editing a config file, restart the client.') }}</dd>
            </div>
            <div class="py-3">
                <dt class="text-sm font-semibold text-slate-900">{{ __('How long does access last?') }}</dt>
                <dd class="mt-1 text-sm leading-relaxed text-slate-600">{{ __('OAuth access tokens are valid for 7 days and are refreshed automatically with a refresh token that is valid for 90 days. Personal access tokens are valid for one year.') }}</dd>
            </div>
            <div class="py-3 last:pb-0">
                <dt class="text-sm font-semibold text-slate-900">{{ __('claude.ai or Claude Desktop cannot connect') }}</dt>
                <dd class="mt-1 text-sm leading-relaxed text-slate-600">{{ __('Custom connectors are reached from the internet, so they need the public HTTPS address of the deployed app, not localhost.') }}</dd>
            </div>
        </dl>
    </section>
</div>
