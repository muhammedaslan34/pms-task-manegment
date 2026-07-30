<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'TaskFlow') }} @yield('title')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 antialiased font-sans">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5">
            <a href="/" class="flex items-center gap-2 font-semibold text-slate-900">
                <svg class="h-7 w-7 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span>TaskFlow</span>
            </a>

            <nav class="flex items-center gap-1 text-sm">
                <a href="/"
                    class="rounded-lg px-3 py-2 font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Submit
                    Task</a>
                @if (auth()->check())
                    <a href="{{ route('admin.tasks.index') }}"
                        class="rounded-lg px-3 py-2 font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Dashboard</a>
                    <span x-data class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="rounded-lg px-3 py-2 font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Sign
                            out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                        class="rounded-lg px-3 py-2 font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Support
                        Login</a>
                @endif
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    @livewireScripts
</body>

</html>
