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

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
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
                    <input id="submitted_by" type="email" wire:model="submitted_by"
                        class="mt-2.5 block w-full rounded-lg border-slate-300 px-3.5 py-3 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="you@example.com">
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

        <aside class="space-y-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <h2 class="font-display text-sm font-semibold text-brand">{{ __('Tips for a good report') }}</h2>
                <ul class="mt-3 space-y-3 text-sm text-slate-600">
                    <li class="flex gap-2.5">
                        <svg class="mt-0.5 h-4 w-4 flex-none text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.54a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.34 8.11" />
                        </svg>
                        {{ __('Include the exact page URL where the issue happens.') }}
                    </li>
                    <li class="flex gap-2.5">
                        <svg class="mt-0.5 h-4 w-4 flex-none text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                        </svg>
                        <span>{{ __('Paste a screenshot with') }} <kbd class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">Ctrl</kbd>+<kbd class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">V</kbd>.</span>
                    </li>
                    <li class="flex gap-2.5">
                        <svg class="mt-0.5 h-4 w-4 flex-none text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        {{ __('List the steps to reproduce the problem.') }}
                    </li>
                </ul>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <h2 class="font-display text-sm font-semibold text-brand">{{ __('Priority guide') }}</h2>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-200">{{ __('High') }}</span>
                        <span class="text-slate-600">{{ __('Blocks work / data loss') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-amber-200">{{ __('Medium') }}</span>
                        <span class="text-slate-600">{{ __('Broken feature, workaround exists') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200">{{ __('Low') }}</span>
                        <span class="text-slate-600">{{ __('Minor / cosmetic') }}</span>
                    </div>
                </dl>
            </div>
        </aside>
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
