<div class="mx-auto flex max-w-md flex-col justify-center py-10">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-slate-900">Support Login</h1>
        <p class="mt-1 text-sm text-slate-500">Sign in to manage incoming tasks.</p>
    </div>

    <form wire:submit="login" class="mt-8 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="email" type="email" wire:model="email" autofocus
                class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input id="password" type="password" wire:model="password"
                class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model="remember"
                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Remember me
        </label>

        <button type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
            wire:loading.attr="disabled" wire:target="login">
            <svg wire:loading wire:target="login" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z" />
            </svg>
            Sign in
        </button>
    </form>

    <p class="mt-4 text-center text-xs text-slate-400">
        Demo admin &mdash; <code class="rounded bg-slate-100 px-1 py-0.5">admin@taskflow.test</code> /
        <code class="rounded bg-slate-100 px-1 py-0.5">password</code>
    </p>
</div>
