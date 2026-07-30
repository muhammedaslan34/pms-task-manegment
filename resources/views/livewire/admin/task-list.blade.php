<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Task Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Review, work on, and resolve incoming support tasks.</p>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach (App\Enums\TaskStatus::cases() as $statusOption)
            <button wire:click="$set('status', '{{ $statusOption->value }}')"
                class="rounded-xl border p-4 text-left transition {{
                    $status === $statusOption->value ? 'border-indigo-500 ring-1 ring-indigo-500 bg-white' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $statusOption->color() }}">
                    {{ $statusOption->label() }}
                </span>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $this->counts[$statusOption->value] }}</p>
            </button>
        @endforeach
    </div>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative flex-1 sm:max-w-xs">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.45 4.4l3.07 3.08a1 1 0 01-1.42 1.41l-3.07-3.07A7 7 0 012 9z" clip-rule="evenodd" />
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tasks..."
                class="w-full rounded-lg border-slate-300 py-2 pl-9 pr-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div class="flex items-center gap-2">
            <select wire:model.live="priority"
                class="rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="all">All priorities</option>
                @foreach (App\Enums\Priority::cases() as $p)
                    <option value="{{ $p->value }}">{{ $p->label() }}</option>
                @endforeach
            </select>

            <select wire:model.live="status"
                class="rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="all">All statuses</option>
                @foreach (App\Enums\TaskStatus::cases() as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>

            @if ($search || $status !== 'all' || $priority !== 'all')
                <button wire:click="$set('search',''), $set('status','all'), $set('priority','all')"
                    class="rounded-lg px-3 py-2 text-sm text-slate-500 hover:bg-slate-100">Clear</button>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">
                            <button wire:click="sort('title')" class="flex items-center gap-1 hover:text-slate-700">
                                Task @if ($sortBy === 'title'){{ $sortDir === 'asc' ? '▲' : '▼' }}@endif
                            </button>
                        </th>
                        <th class="hidden px-4 py-3 lg:table-cell">Priority</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="hidden px-4 py-3 md:table-cell">Assigned</th>
                        <th class="px-4 py-3">
                            <button wire:click="sort('created_at')" class="flex items-center gap-1 hover:text-slate-700">
                                Reported @if ($sortBy === 'created_at'){{ $sortDir === 'asc' ? '▲' : '▼' }}@endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($tasks as $task)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.tasks.show', $task) }}" class="block">
                                    <span class="font-medium text-slate-900 hover:text-indigo-600">{{ $task->title }}</span>
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
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $task->status->color() }}">
                                    {{ $task->status->label() }}
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 text-slate-500 md:table-cell">
                                {{ $task->assignee?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-500">
                                {{ $task->created_at->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="openManage({{ $task->id }})"
                                    class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                    Manage
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <svg class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a2 2 0 011.4.6L19 8.4a2 2 0 01.6 1.4V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-3 text-sm text-slate-500">No tasks match your filters.</p>
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
                        <p class="mt-0.5 text-xs text-slate-500">Reported {{ $managing->created_at->diffForHumans() }}</p>
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
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Description</span>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $managing->description }}</p>
                        </div>
                    @endif

                    @if ($managing->page_link)
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Page link</span>
                            <a href="{{ $managing->page_link }}" target="_blank" rel="noopener"
                                class="mt-1 block break-all text-sm text-indigo-600 hover:underline">{{ $managing->page_link }}</a>
                        </div>
                    @endif

                    @if ($managing->screenshotUrl())
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Screenshot</span>
                            <a href="{{ $managing->screenshotUrl() }}" target="_blank" rel="noopener" class="mt-1 block">
                                <img src="{{ $managing->screenshotUrl() }}" alt="Screenshot"
                                    class="max-h-56 rounded-lg border border-slate-200">
                            </a>
                        </div>
                    @endif

                    <hr class="border-slate-100">

                    <div>
                        <label for="form-status" class="block text-sm font-medium text-slate-700">Status</label>
                        <select id="form-status" wire:model="form.status"
                            class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach (App\Enums\TaskStatus::cases() as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="form.assign_to_me"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Assign to me ({{ auth()->user()->name }})
                    </label>

                    <div>
                        <label for="form-resolution" class="block text-sm font-medium text-slate-700">
                            Resolution / note @if ($form->status === \App\Enums\TaskStatus::Completed->value)<span class="text-red-500">*</span>@endif
                        </label>
                        <textarea id="form-resolution" wire:model="form.resolution_note" rows="3"
                            class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Describe what you did to resolve this task..."></textarea>
                        @error('form.resolution_note')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </form>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button wire:click="closeManage"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button wire:click="saveManage"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        Save changes
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
