<div x-data="{ showSuccess: false }" x-on:task-submitted.window="showSuccess = true; setTimeout(() => showSuccess = false, 5000)">
    <div x-show="showSuccess" x-transition x-cloak
        class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <svg class="mt-0.5 h-5 w-5 flex-none text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z"
                clip-rule="evenodd" />
        </svg>
        <div>
            <p class="font-semibold">Task submitted!</p>
            <p>Our support team will review it shortly.</p>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-slate-900">Report an issue</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Found a bug or something that needs fixing? Tell us about it and we'll get on it.
                </p>
            </div>

            <form wire:submit="save" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <label for="title" class="block text-sm font-medium text-slate-700">Title <span
                            class="text-red-500">*</span></label>
                    <input id="title" type="text" wire:model="title"
                        class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="e.g. Login button doesn't respond on click">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="page_link" class="block text-sm font-medium text-slate-700">Page link (URL)</label>
                    <input id="page_link" type="url" wire:model="page_link"
                        class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="https://yoursite.com/problem-page">
                    @error('page_link')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="description" wire:model="description" rows="5"
                        class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Describe what happened, what you expected, and steps to reproduce it..."></textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <span class="block text-sm font-medium text-slate-700">Priority <span
                            class="text-red-500">*</span></span>
                    <div class="mt-2 grid grid-cols-3 gap-3">
                        @foreach (App\Enums\Priority::cases() as $priorityOption)
                            <label
                                class="flex cursor-pointer items-center justify-center rounded-lg border px-3 py-2.5 text-sm font-medium transition {{
                                    $priority === $priorityOption->value
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-500'
                                        : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
                                <input type="radio" wire:model="priority" value="{{ $priorityOption->value }}"
                                    class="sr-only">
                                {{ $priorityOption->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('priority')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <span class="block text-sm font-medium text-slate-700">Screenshot</span>
                    <div
                        x-data="pasteScreenshot()"
                        x-init="init()"
                        x-on:paste.window="handlePaste($event)"
                        @drop.prevent="handleDrop($event)"
                        @dragover.prevent="$el.classList.add('border-indigo-400', 'bg-indigo-50')"
                        @dragleave.prevent="$el.classList.remove('border-indigo-400', 'bg-indigo-50')"
                        class="mt-2 cursor-pointer rounded-xl border-2 border-dashed border-slate-300 p-6 text-center transition hover:border-slate-400"
                        @click="$refs.fileInput.click()">

                        <input type="file" x-ref="fileInput" wire:model="screenshot" accept="image/*"
                            class="hidden" />

                        @if ($screenshot)
                            <div class="space-y-3">
                                <img src="{{ $screenshot->temporaryUrl() }}" alt="Preview"
                                    class="mx-auto max-h-64 rounded-lg shadow-sm">
                                <button type="button" wire:click="removeScreenshot"
                                    @click.stop
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-100">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Remove screenshot
                                </button>
                            </div>
                        @else
                            <svg class="mx-auto h-10 w-10 text-slate-400" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 18.75h.008v.008H18v-.008z" />
                            </svg>
                            <p class="mt-2 text-sm text-slate-600">
                                <span class="font-medium text-indigo-600">Click to upload</span>,
                                <span class="font-semibold">paste from clipboard</span>, or drag &amp; drop
                            </p>
                            <p class="mt-1 text-xs text-slate-400">PNG, JPG, GIF up to 10MB</p>
                        @endif
                    </div>
                    @error('screenshot')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="submitted_by" class="block text-sm font-medium text-slate-700">Your email
                        <span class="font-normal text-slate-400">(optional)</span></label>
                    <input id="submitted_by" type="email" wire:model="submitted_by"
                        class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="you@example.com">
                    @error('submitted_by')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                        wire:loading.attr="disabled">
                        <svg wire:loading.remove wire:target="save" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9.13 2.01a1 1 0 00-.86.99v3.5H4a1 1 0 100 2h4.27v3.5a1 1 0 102 0v-3.5h4.27a1 1 0 100-2H10.27V3a1 1 0 00-1.14-.99z" />
                            <path d="M3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" />
                        </svg>
                        <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z" />
                        </svg>
                        Submit Task
                    </button>
                </div>
            </form>
        </div>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Tips for a good report</h2>
                <ul class="mt-3 space-y-2.5 text-sm text-slate-600">
                    <li class="flex gap-2">
                        <svg class="mt-0.5 h-4 w-4 flex-none text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd" />
                        </svg>
                        Include the exact page URL where the issue happens.
                    </li>
                    <li class="flex gap-2">
                        <svg class="mt-0.5 h-4 w-4 flex-none text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd" />
                        </svg>
                        Paste a screenshot with <kbd class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">Ctrl</kbd>+<kbd class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">V</kbd>.
                    </li>
                    <li class="flex gap-2">
                        <svg class="mt-0.5 h-4 w-4 flex-none text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd" />
                        </svg>
                        List the steps to reproduce the problem.
                    </li>
                </ul>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Priority guide</h2>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-200">High</span>
                        <span class="text-slate-600">Blocks work / data loss</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-amber-200">Medium</span>
                        <span class="text-slate-600">Broken feature, workaround exists</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200">Low</span>
                        <span class="text-slate-600">Minor / cosmetic</span>
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
