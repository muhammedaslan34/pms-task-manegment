<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-brand">{{ __('Users') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('Manage support team accounts.') }}</p>
        </div>
        <button wire:click="createUser"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-900">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16z" clip-rule="evenodd" />
            </svg>
            {{ __('New User') }}
        </button>
    </div>

    <div class="mb-5 rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm">
        <div class="relative sm:max-w-md">
            <span class="pointer-events-none absolute inset-y-0 start-0 flex w-11 items-center justify-center text-slate-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </span>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search by name or email...') }}"
                class="w-full rounded-xl border border-slate-200 bg-white py-3 pe-10 ps-11 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
            @if ($search !== '')
                <button
                    type="button"
                    wire:click="$set('search', '')"
                    class="absolute inset-y-0 end-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-600"
                    aria-label="{{ __('Clear') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
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
                        wire:confirm="{{ __('Delete the selected user(s)? You cannot be deleted. This cannot be undone.') }}"
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
                <thead class="bg-slate-50">
                    <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" wire:model.live="selectAllPage"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-3">{{ __('Name') }}</th>
                        <th class="px-4 py-3">{{ __('Email') }}</th>
                        <th class="hidden px-4 py-3 sm:table-cell">{{ __('Created') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($users as $user)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-3">
                                @if ($user->id === auth()->id())
                                    <span class="block w-4"></span>
                                @else
                                    <input type="checkbox" wire:model.live="selected.{{ $user->id }}"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-800">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                    <span class="font-medium text-slate-900">
                                        {{ $user->name }}
                                        @if ($user->is_admin)
                                            <span class="ms-1 rounded bg-blue-100 px-1.5 py-0.5 text-xs font-medium text-blue-800">{{ __('Admin') }}</span>
                                        @endif
                                        @if ($user->id === auth()->id())
                                            <span class="ms-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">{{ __('you') }}</span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="hidden px-4 py-3 text-slate-500 sm:table-cell">{{ $user->created_at->format('Y/m/d') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="editUser({{ $user->id }})"
                                        class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">{{ __('Edit') }}</button>
                                    <button wire:click="confirmDelete({{ $user->id }})" @if ($user->id === auth()->id()) disabled @endif
                                        class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-40">{{ __('Delete') }}</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center text-sm text-slate-500">{{ __('No users found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>

    @if ($formModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="closeForm"></div>

            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ $form->editing ? __('Edit User') : __('New User') }}
                    </h2>
                    <button wire:click="closeForm" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4 px-6 py-5">
                    <div>
                        <label for="form-name" class="block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
                        <input id="form-name" type="text" wire:model="form.name"
                            class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('form.name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="form-email" class="block text-sm font-medium text-slate-700">{{ __('Email') }}</label>
                        <input id="form-email" type="email" wire:model="form.email"
                            class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('form.email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="form-password" class="block text-sm font-medium text-slate-700">
                            {{ __('Password') }}
                            @if ($form->editing)
                                <span class="font-normal text-slate-400">{{ __('(leave blank to keep current)') }}</span>
                            @endif
                        </label>
                        <input id="form-password" type="password" wire:model="form.password"
                            class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('form.password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <input type="checkbox" wire:model="form.is_admin"
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-slate-700">{{ __('Administrator') }}</span>
                        <span class="text-xs text-slate-400">{{ __('Grants access to manage users.') }}</span>
                    </label>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                        <button type="button" wire:click="closeForm"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">{{ __('Cancel') }}</button>
                        <button type="submit"
                            class="rounded-lg bg-blue-800 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-900">
                            {{ $form->editing ? __('Save changes') : __('Create user') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($deleteModalOpen && $deleting)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="closeDelete"></div>

            <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl">
                <div class="px-6 py-5 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ __('Delete user?') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ __(':name (:email) will be permanently removed.', ['name' => $deleting->name, 'email' => $deleting->email]) }}
                    </p>
                </div>
                <div class="flex items-center justify-center gap-3 border-t border-slate-200 px-6 py-4">
                    <button wire:click="closeDelete"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">{{ __('Cancel') }}</button>
                    <button wire:click="destroy"
                        class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
