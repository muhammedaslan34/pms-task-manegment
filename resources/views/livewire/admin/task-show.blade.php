<div>
    <a href="{{ route('admin.tasks.index') }}"
        class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-700">
        <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.7 5.3a1 1 0 010 1.4L9.4 10l3.3 3.3a1 1 0 01-1.4 1.4l-4-4a1 1 0 010-1.4l4-4a1 1 0 011.4 0z" clip-rule="evenodd" />
        </svg>
        {{ __('Back to all tasks') }}
    </a>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="font-display text-xl font-bold text-brand">{{ $task->title }}</h1>
                        <p class="mt-1 text-xs text-slate-400">{{ __('Task #:id · Reported :time', ['id' => $task->id, 'time' => $task->created_at->diffForHumans()]) }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $task->status->color() }}">
                        {{ $task->status->label() }}
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $task->priority->color() }}">
                        {{ __(':label priority', ['label' => $task->priority->label()]) }}
                    </span>
                    @if ($task->submitted_by)
                        <span class="text-slate-500">{{ __('from') }} <a href="mailto:{{ $task->submitted_by }}" class="text-blue-600 hover:underline">{{ $task->submitted_by }}</a></span>
                    @endif
                </div>

                @if ($task->description)
                    <div class="mt-6">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Description') }}</h2>
                        <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ $task->description }}</p>
                    </div>
                @endif

                @if ($task->page_link)
                    <div class="mt-6">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Page link') }}</h2>
                        <a href="{{ $task->page_link }}" target="_blank" rel="noopener"
                            class="mt-2 inline-flex items-center gap-1.5 break-all text-sm text-blue-600 hover:underline">
                            {{ $task->page_link }}
                            <svg class="h-3.5 w-3.5 flex-none" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M11 3a1 1 0 100 2h2.59l-6.3 6.29a1 1 0 001.42 1.42l6.29-6.3V11a1 1 0 102 0V5a1 1 0 00-1-1h-5z" />
                                <path fill-rule="evenodd" d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                @endif

                @if ($task->screenshotUrl())
                    <div class="mt-6">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Screenshot') }}</h2>
                        <a href="{{ $task->screenshotUrl() }}" target="_blank" rel="noopener" class="mt-2 block">
                            <img src="{{ $task->screenshotUrl() }}" alt="{{ __('Screenshot') }}"
                                class="max-h-96 rounded-xl border border-slate-200 shadow-sm">
                        </a>
                    </div>
                @endif

                @if ($task->resolution_note)
                    <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ __('Resolution') }}</h2>
                        <p class="mt-1 whitespace-pre-wrap text-sm text-emerald-900">{{ $task->resolution_note }}</p>
                    </div>
                @endif
            </div>
        </div>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <h2 class="font-display text-sm font-semibold text-brand">{{ __('Manage task') }}</h2>

                <dl class="mt-3 space-y-2 text-sm">
                    @if ($task->completed_at)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ __('Completed') }}</dt>
                            <dd class="font-medium text-slate-900">{{ $task->completed_at->diffForHumans() }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-4">
                    <label for="status-select" class="block text-sm font-medium text-slate-700">{{ __('Status') }}</label>
                    <div class="relative mt-1.5">
                        <select id="status-select" wire:model.live="form.status" wire:loading.attr="disabled" wire:target="form.status"
                            class="block w-full appearance-none rounded-lg border-slate-300 py-2 ps-3 pe-9 text-sm font-medium shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach (\App\Enums\TaskStatus::cases() as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                        <span wire:loading wire:target="form.status" class="absolute end-9 top-1/2 -translate-y-1/2">
                            <svg class="h-4 w-4 animate-spin text-blue-500" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z" />
                            </svg>
                        </span>
                        <svg class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">{{ __('Changes save automatically.') }}</p>
                </div>

                <div class="mt-5">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Quick actions') }}</p>
                    <div class="space-y-2">
                    @if ($task->status !== App\Enums\TaskStatus::InProgress)
                        <button wire:click="setInProgress" wire:loading.attr="disabled"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.3 2.84A1 1 0 005 3.73v12.54a1 1 0 001.5.87l10-6.27a1 1 0 000-1.74l-10-6.29z" /></svg>
                            {{ __('Start working') }}
                        </button>
                    @endif

                    @if ($task->status !== App\Enums\TaskStatus::Completed)
                        <button wire:click="markCompleted"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd" /></svg>
                            {{ __('Mark as completed') }}
                        </button>
                    @endif

                    @if ($task->status === App\Enums\TaskStatus::Completed)
                        <button wire:click="reopen"
                            class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            {{ __('Reopen task') }}
                        </button>
                    @endif
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <h2 class="font-display text-sm font-semibold text-brand">{{ __('Timeline') }}</h2>
                <ol class="mt-3 space-y-3 text-sm">
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 flex-none rounded-full bg-slate-300"></span>
                        <span class="text-slate-600">{{ __('Reported') }} <span class="font-medium text-slate-900">{{ $task->created_at->translatedFormat('j M Y g:i A') }}</span></span>
                    </li>
                    @if ($task->updated_at != $task->created_at)
                        <li class="flex gap-3">
                            <span class="mt-1 h-2 w-2 flex-none rounded-full bg-blue-400"></span>
                            <span class="text-slate-600">{{ __('Updated') }} <span class="font-medium text-slate-900">{{ $task->updated_at->translatedFormat('j M Y g:i A') }}</span></span>
                        </li>
                    @endif
                    @if ($task->completed_at)
                        <li class="flex gap-3">
                            <span class="mt-1 h-2 w-2 flex-none rounded-full bg-emerald-500"></span>
                            <span class="text-slate-600">{{ __('Completed') }} <span class="font-medium text-slate-900">{{ $task->completed_at->translatedFormat('j M Y g:i A') }}</span></span>
                        </li>
                    @endif
                </ol>
            </div>
        </aside>
    </div>

    @if ($confirmOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="closeConfirm"></div>

            <form wire:submit="save" class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('Mark task as completed') }}</h2>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label for="form-resolution" class="block text-sm font-medium text-slate-700">{{ __('Resolution note') }}</label>
                        <textarea id="form-resolution" wire:model="form.resolution_note" rows="4" autofocus
                            class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            placeholder="{{ __('What did you do to solve this?') }}"></textarea>
                        @error('form.resolution_note')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button type="button" wire:click="closeConfirm"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">{{ __('Cancel') }}</button>
                    <button type="submit"
                        class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        {{ __('Confirm completion') }}
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
