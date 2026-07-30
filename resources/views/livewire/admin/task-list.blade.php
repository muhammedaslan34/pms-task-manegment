<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-brand">{{ __('Task Dashboard') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('Review, work on, and resolve incoming support tasks.') }}</p>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach (App\Enums\TaskStatus::cases() as $statusOption)
            <div class="relative overflow-hidden rounded-2xl border bg-gradient-to-br p-5 shadow-sm {{ $statusOption->cardClasses() }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-slate-600">{{ $statusOption->label() }}</p>
                        <p class="mt-2 font-display text-3xl font-bold tracking-tight text-brand">
                            {{ $this->counts[$statusOption->value] }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400">{{ __('tasks') }}</p>
                    </div>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl {{ $statusOption->iconClasses() }}">
                        @if ($statusOption === App\Enums\TaskStatus::Pending)
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif ($statusOption === App\Enums\TaskStatus::InProgress)
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z" />
                            </svg>
                        @else
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between lg:gap-4">
        <div class="relative flex-1 lg:max-w-sm">
            <span class="pointer-events-none absolute inset-y-0 start-0 flex w-11 items-center justify-center text-slate-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </span>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search tasks...') }}"
                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pe-3.5 ps-11 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Priority filter --}}
            <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                <button type="button" @click="open = !open"
                    class="inline-flex min-w-[9.5rem] items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                    <span>
                        {{ $priority === 'all' ? __('All priorities') : \App\Enums\Priority::from($priority)->label() }}
                    </span>
                    <svg class="h-4 w-4 text-slate-400 transition" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-cloak x-transition.opacity.duration.100ms
                    class="absolute start-0 z-30 mt-1.5 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1.5 shadow-lg ring-1 ring-black/5">
                    <button type="button" wire:click="$set('priority', 'all')" @click="open = false"
                        class="flex w-full items-center gap-2.5 px-3.5 py-2 text-start text-sm transition {{ $priority === 'all' ? 'bg-blue-50 font-semibold text-blue-800' : 'text-slate-700 hover:bg-slate-50' }}">
                        {{ __('All priorities') }}
                    </button>
                    @foreach (App\Enums\Priority::cases() as $p)
                        <button type="button" wire:click="$set('priority', '{{ $p->value }}')" @click="open = false"
                            class="flex w-full items-center gap-2.5 px-3.5 py-2 text-start text-sm transition {{ $priority === $p->value ? 'bg-blue-50 font-semibold text-blue-800' : 'text-slate-700 hover:bg-slate-50' }}">
                            <span class="inline-flex h-2 w-2 flex-none rounded-full {{
                                $p === App\Enums\Priority::High ? 'bg-red-500' :
                                ($p === App\Enums\Priority::Medium ? 'bg-amber-500' : 'bg-slate-400')
                            }}"></span>
                            {{ $p->label() }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Status filter --}}
            <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                <button type="button" @click="open = !open"
                    class="inline-flex min-w-[9.5rem] items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                    <span>
                        {{ $status === 'all' ? __('All statuses') : \App\Enums\TaskStatus::from($status)->label() }}
                    </span>
                    <svg class="h-4 w-4 text-slate-400 transition" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-cloak x-transition.opacity.duration.100ms
                    class="absolute start-0 z-30 mt-1.5 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1.5 shadow-lg ring-1 ring-black/5">
                    <button type="button" wire:click="$set('status', 'all')" @click="open = false"
                        class="flex w-full items-center gap-2.5 px-3.5 py-2 text-start text-sm transition {{ $status === 'all' ? 'bg-blue-50 font-semibold text-blue-800' : 'text-slate-700 hover:bg-slate-50' }}">
                        {{ __('All statuses') }}
                    </button>
                    @foreach (App\Enums\TaskStatus::cases() as $s)
                        <button type="button" wire:click="$set('status', '{{ $s->value }}')" @click="open = false"
                            class="flex w-full items-center gap-2.5 px-3.5 py-2 text-start text-sm transition {{ $status === $s->value ? 'bg-blue-50 font-semibold text-blue-800' : 'text-slate-700 hover:bg-slate-50' }}">
                            <span class="inline-flex h-2 w-2 flex-none rounded-full {{
                                $s === App\Enums\TaskStatus::Pending ? 'bg-slate-400' :
                                ($s === App\Enums\TaskStatus::InProgress ? 'bg-blue-500' : 'bg-emerald-500')
                            }}"></span>
                            {{ $s->label() }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Per page --}}
            <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                <button type="button" @click="open = !open"
                    class="inline-flex min-w-[7.5rem] items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                    <span>{{ __(':count / page', ['count' => $perPage]) }}</span>
                    <svg class="h-4 w-4 text-slate-400 transition" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-cloak x-transition.opacity.duration.100ms
                    class="absolute start-0 z-30 mt-1.5 w-40 overflow-hidden rounded-xl border border-slate-200 bg-white py-1.5 shadow-lg ring-1 ring-black/5">
                    @foreach ([20, 50, 100] as $count)
                        <button type="button" wire:click="$set('perPage', {{ $count }})" @click="open = false"
                            class="flex w-full items-center justify-between gap-2 px-3.5 py-2 text-start text-sm transition {{ $perPage === $count ? 'bg-blue-50 font-semibold text-blue-800' : 'text-slate-700 hover:bg-slate-50' }}">
                            <span>{{ $count }}</span>
                            <span class="text-xs font-normal text-slate-400">{{ __('records') }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            @if ($search || $status !== 'all' || $priority !== 'all')
                <button wire:click="$set('search',''), $set('status','all'), $set('priority','all')"
                    class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-blue-800 hover:bg-blue-50">{{ __('Clear') }}</button>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
        @if ($this->selectedCount > 0)
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-blue-50 px-4 py-2.5 text-sm">
                <span class="font-medium text-blue-800">
                    {{ $this->selectedCount }} {{ __('selected') }}
                </span>
                <div class="flex items-center gap-2">
                    <button wire:click="clearSelection"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100">
                        {{ __('Clear') }}
                    </button>
                    <button wire:click="deleteSelected"
                        wire:confirm="{{ __('Delete the selected task(s)? This cannot be undone.') }}"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ __('Delete selected') }}
                    </button>
                </div>
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100/80">
                    <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" wire:model.live="selectAllPage"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-3">
                            <button wire:click="sort('title')" class="flex items-center gap-1 hover:text-slate-700">
                                {{ __('Task') }} @if ($sortBy === 'title'){{ $sortDir === 'asc' ? '▲' : '▼' }}@endif
                            </button>
                        </th>
                        <th class="hidden px-4 py-3 lg:table-cell">{{ __('Priority') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">
                            <button wire:click="sort('created_at')" class="flex items-center gap-1 hover:text-slate-700">
                                {{ __('Reported') }} @if ($sortBy === 'created_at'){{ $sortDir === 'asc' ? '▲' : '▼' }}@endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($tasks as $task)
                        <tr class="transition hover:bg-blue-50/40">
                            <td class="px-4 py-3">
                                <input type="checkbox" wire:model.live="selected.{{ $task->id }}"
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.tasks.show', $task) }}" class="block">
                                    <span class="font-medium text-slate-900 hover:text-blue-600">{{ $task->title }}</span>
                                    @if ($task->page_link)
                                        <span class="mt-0.5 block max-w-xs truncate text-xs text-slate-400">{{ $task->page_link }}</span>
                                    @endif
                                </a>
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $task->priority->color() }}">
                                    {{ $task->priority->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    x-data="{
                                        open: false,
                                        style: '',
                                        toggle() {
                                            this.open = ! this.open;
                                            if (this.open) {
                                                this.$nextTick(() => this.place());
                                            }
                                        },
                                        place() {
                                            const btn = this.$refs.btn;
                                            if (! btn) return;

                                            const r = btn.getBoundingClientRect();
                                            const menuH = 140;
                                            const menuW = Math.max(r.width, 176);
                                            const gap = 6;
                                            const openUp = (window.innerHeight - r.bottom) < (menuH + gap + 8);
                                            const top = openUp ? Math.max(8, r.top - menuH - gap) : (r.bottom + gap);
                                            let left = r.left;
                                            if (left + menuW > window.innerWidth - 8) {
                                                left = Math.max(8, r.right - menuW);
                                            }
                                            this.style = `position:fixed;top:${top}px;left:${left}px;width:${menuW}px;z-index:80;`;
                                        },
                                    }"
                                    @click.outside="open = false"
                                    @keydown.escape.window="open = false"
                                    @scroll.window="if (open) place()"
                                    @resize.window="if (open) place()"
                                    class="relative inline-block min-w-[8.5rem]"
                                    wire:key="status-{{ $task->id }}-{{ $task->status->value }}">
                                    <button
                                        type="button"
                                        x-ref="btn"
                                        @click="toggle()"
                                        class="inline-flex w-full items-center justify-between gap-1.5 rounded-full px-2.5 py-1.5 text-xs font-medium ring-1 transition hover:opacity-90 {{ $task->status->color() }}">
                                        <span>{{ $task->status->label() }}</span>
                                        <svg class="h-3.5 w-3.5 opacity-60" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <template x-teleport="body">
                                        <div
                                            x-show="open"
                                            x-transition.opacity.duration.100ms
                                            x-cloak
                                            :style="style"
                                            class="rounded-xl border border-slate-200 bg-white py-1 shadow-lg ring-1 ring-black/5">
                                            @foreach (App\Enums\TaskStatus::cases() as $s)
                                                <button
                                                    type="button"
                                                    wire:click="updateStatus({{ $task->id }}, '{{ $s->value }}')"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2 px-3 py-2 text-start text-xs font-medium text-slate-700 transition hover:bg-slate-50 {{ $task->status === $s ? 'bg-slate-50' : '' }}">
                                                    <span class="inline-flex h-2 w-2 flex-none rounded-full {{
                                                        $s === App\Enums\TaskStatus::Pending ? 'bg-slate-400' :
                                                        ($s === App\Enums\TaskStatus::InProgress ? 'bg-blue-500' : 'bg-emerald-500')
                                                    }}"></span>
                                                    {{ $s->label() }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-500">
                                {{ $task->created_at->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3 text-end">
                                <button wire:click="openManage({{ $task->id }})"
                                    class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-800 transition hover:bg-blue-100">
                                    {{ __('Manage') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <svg class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a2 2 0 011.4.6L19 8.4a2 2 0 01.6 1.4V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-3 text-sm text-slate-500">{{ __('No tasks match your filters.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $tasks->links() }}
    </div>

    @if ($manageModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="closeManage"></div>

            <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $managing->title }}</h2>
                        <p class="mt-0.5 text-xs text-slate-500">{{ __('Reported :time', ['time' => $managing->created_at->diffForHumans()]) }}</p>
                    </div>
                    <button wire:click="closeManage" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveManage" class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-5">
                    @if ($managing->description)
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Description') }}</span>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $managing->description }}</p>
                        </div>
                    @endif

                    @if ($managing->page_link)
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Page link') }}</span>
                            <a href="{{ $managing->page_link }}" target="_blank" rel="noopener"
                                class="mt-1 block break-all text-sm text-blue-600 hover:underline">{{ $managing->page_link }}</a>
                        </div>
                    @endif

                    @if ($managing->images->isNotEmpty())
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Screenshots') }}</span>
                            <div class="mt-1 grid grid-cols-3 gap-2">
                                @foreach ($managing->images as $image)
                                    <a href="{{ $image->imageUrl() }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-slate-200">
                                        <img src="{{ $image->imageUrl() }}" alt="{{ __('Screenshot') }}"
                                            class="h-24 w-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <hr class="border-slate-100">

                    <div>
                        <label for="form-resolution" class="block text-sm font-medium text-slate-700">
                            {{ __('Resolution / note') }}
                        </label>
                        <textarea id="form-resolution" wire:model="form.resolution_note" rows="3"
                            class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="{{ __('Describe what you did to resolve this task...') }}"></textarea>
                        @error('form.resolution_note')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </form>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button wire:click="closeManage"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">{{ __('Cancel') }}</button>
                    <button wire:click="saveManage"
                        class="rounded-lg bg-blue-800 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900">
                        {{ __('Save changes') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
