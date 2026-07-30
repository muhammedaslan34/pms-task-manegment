<div x-data="{ showSuccess: false }" x-on:task-submitted.window="showSuccess = true; setTimeout(() => showSuccess = false, 5000)">
    <div x-show="showSuccess" x-transition x-cloak
        class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <svg class="mt-0.5 h-5 w-5 flex-none text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <p class="font-semibold">{{ __('Task submitted!') }}</p>
            <p>{{ __('Our support team will review it shortly.') }}</p>
        </div>
    </div>

    <div class="mx-auto max-w-3xl">
            <div class="mb-6">
                <h1 class="font-display text-2xl font-bold text-brand">{{ __('Report an issue') }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ __('Found a bug or something that needs fixing? Tell us about it and we\'ll get on it.') }}
                </p>
            </div>

            <form wire:submit="save" class="space-y-7 rounded-2xl border border-slate-200/80 bg-white p-7 shadow-sm sm:p-8">
                <div>
                    <label for="title" class="block text-sm font-medium text-slate-700">{{ __('Title') }} <span
                            class="text-red-500">*</span></label>
                    <input id="title" type="text" wire:model="title"
                        class="mt-2.5 block w-full rounded-lg border-slate-300 px-3.5 py-3 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="{{ __('e.g. Login button doesn\'t respond on click') }}">
                    @error('title')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="page_link" class="block text-sm font-medium text-slate-700">{{ __('Page link (URL)') }}
                        <span class="font-normal text-slate-400">{{ __('(optional)') }}</span></label>
                    <input id="page_link" type="url" wire:model="page_link"
                        class="mt-2.5 block w-full rounded-lg border-slate-300 px-3.5 py-3 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="https://yoursite.com/problem-page">
                    @error('page_link')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
                    <textarea id="description" wire:model="description" rows="5"
                        class="mt-2.5 block w-full rounded-lg border-slate-300 px-3.5 py-3 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="{{ __('Describe what happened, what you expected, and steps to reproduce it...') }}"></textarea>
                    @error('description')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <span class="block text-sm font-medium text-slate-700">{{ __('Priority') }} <span
                            class="text-red-500">*</span></span>
                    <div class="mt-3 grid grid-cols-3 gap-3">
                        @foreach (App\Enums\Priority::cases() as $priorityOption)
                            <label
                                class="flex cursor-pointer items-center justify-center rounded-lg border px-3 py-3 text-sm font-medium transition {{
                                    $priority === $priorityOption->value
                                        ? 'border-blue-800 bg-blue-50 text-blue-800 ring-1 ring-blue-800'
                                        : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
                                <input type="radio" wire:model="priority" value="{{ $priorityOption->value }}"
                                    class="sr-only">
                                {{ $priorityOption->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('priority')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <span class="block text-sm font-medium text-slate-700">{{ __('Screenshot') }}</span>
                    <div
                        x-data="pasteScreenshot()"
                        x-init="init()"
                        x-on:paste.window="handlePaste($event)"
                        @drop.prevent="handleDrop($event)"
                        @dragover.prevent="$el.classList.add('border-blue-400', 'bg-blue-50')"
                        @dragleave.prevent="$el.classList.remove('border-blue-400', 'bg-blue-50')"
                        class="mt-3 cursor-pointer rounded-xl border-2 border-dashed border-slate-300 bg-slate-50/50 p-8 text-center transition hover:border-blue-400 hover:bg-blue-50/50"
                        @click="$refs.fileInput.click()">

                        <input type="file" x-ref="fileInput" wire:model="screenshot" accept="image/*"
                            class="hidden" />

                        @if ($screenshot)
                            <div class="space-y-3">
                                <img src="{{ $screenshot->temporaryUrl() }}" alt="{{ __('Preview') }}"
                                    class="mx-auto max-h-64 rounded-lg shadow-sm">
                                <button type="button" wire:click="removeScreenshot"
                                    @click.stop
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-100">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {{ __('Remove screenshot') }}
                                </button>
                            </div>
                        @else
                            <svg class="mx-auto h-11 w-11 text-blue-500" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                            </svg>
                            <p class="mt-3 text-sm text-slate-600">
                                <span class="font-medium text-blue-700">{{ __('Click to upload') }}</span>،
                                <span class="font-semibold">{{ __('paste from clipboard') }}</span>،
                                {{ __('or drag & drop') }}
                            </p>
                            <p class="mt-1.5 text-xs text-slate-400">{{ __('PNG, JPG, GIF up to 10MB') }}</p>
                        @endif
                    </div>
                    @error('screenshot')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="submitted_by" class="block text-sm font-medium text-slate-700">{{ __('Your email') }}
                        <span class="font-normal text-slate-400">{{ __('(optional)') }}</span></label>

                    @auth
                        <input id="submitted_by" type="email" wire:model="submitted_by" readonly
                            class="mt-2.5 block w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-100 px-3.5 py-3 text-slate-500 shadow-sm"
                            placeholder="you@example.com">
                        <p class="mt-1.5 text-xs text-slate-400">{{ __('Signed in as :email', ['email' => auth()->user()->email]) }}</p>
                    @else
                        <select wire:model="submitted_by"
                            class="mt-2.5 block w-full rounded-lg border-slate-300 px-3.5 py-3 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">{{ __('— Select a user (or leave blank) —') }}</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->email }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-slate-400">{{ __('Pick the account submitting this task.') }}</p>
                    @endauth

                    @error('submitted_by')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-800 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900 focus:ring-2 focus:ring-blue-800 focus:ring-offset-2 disabled:opacity-50"
                        wire:loading.attr="disabled">
                        <svg wire:loading.remove wire:target="save" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                        <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z" />
                        </svg>
                        {{ __('Submit Task') }}
                    </button>
                </div>
            </form>
    </div>

    <script>
        function pasteScreenshot() {
            return {
                init() {},
                pushFile(file) {
                    if (!file || !file.type.startsWith('image/')) return;
                    const input = this.$refs.fileInput;
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                },
                handlePaste(e) {
                    const items = e.clipboardData?.items;
                    if (!items) return;
                    for (const item of items) {
                        if (item.type.startsWith('image/')) {
                            const file = item.getAsFile();
                            if (file) {
                                e.preventDefault();
                                this.pushFile(file);
                                break;
                            }
                        }
                    }
                },
                handleDrop(e) {
                    const file = e.dataTransfer?.files?.[0];
                    if (file) this.pushFile(file);
                },
            }
        }
    </script>
</div>
