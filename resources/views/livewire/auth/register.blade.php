<div class="mx-auto flex max-w-md flex-col justify-center py-10">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-slate-900">{{ __('Create an account') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('Create your support account to manage tasks.') }}</p>
    </div>

    <form wire:submit="register" class="mt-8 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
            <input id="name" type="text" wire:model="name" autofocus
                class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">{{ __('Email') }}</label>
            <input id="email" type="email" wire:model="email"
                class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">{{ __('Password') }}</label>
            <input id="password" type="password" wire:model="password"
                class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">{{ __('Confirm password') }}</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation"
                class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <button type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
            wire:loading.attr="disabled" wire:target="register">
            <svg wire:loading wire:target="register" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z" />
            </svg>
            {{ __('Sign up') }}
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-500">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:underline">{{ __('Sign in') }}</a>
    </p>
</div>
