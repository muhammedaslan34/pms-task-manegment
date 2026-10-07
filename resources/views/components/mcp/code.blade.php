@props(['code', 'variant' => 'dark', 'wrap' => false])

@php
    $boxClasses = match ($variant) {
        'light' => 'border-slate-200 bg-slate-50 text-slate-800',
        'highlight' => 'border-amber-300 bg-amber-50 text-amber-950',
        default => 'border-slate-800 bg-slate-900 text-slate-100',
    };
    $buttonClasses = $variant === 'dark'
        ? 'bg-white/10 text-slate-200 hover:bg-white/20'
        : 'bg-white text-slate-600 shadow-sm ring-1 ring-slate-200 hover:bg-slate-100';
@endphp

{{-- Copyable code block. Always LTR, even on Arabic pages; long lines scroll inside the box. --}}
<div {{ $attributes->merge(['class' => 'relative min-w-0 rounded-xl border '.$boxClasses]) }}
    dir="ltr"
    x-data="{
        copied: false,
        timer: null,
        async copy() {
            const text = this.$refs.code.innerText;
            try {
                await navigator.clipboard.writeText(text);
                this.done();
            } catch (e) {
                // No clipboard API (e.g. plain http): select the text so it can be copied manually.
                const range = document.createRange();
                range.selectNodeContents(this.$refs.code);
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                try { if (document.execCommand('copy')) this.done(); } catch (e2) {}
            }
        },
        done() {
            this.copied = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.copied = false, 2000);
        },
    }">
    <pre @class([
        'py-3 pe-24 ps-4 text-left font-mono text-[13px] leading-relaxed',
        'max-h-48 overflow-y-auto whitespace-pre-wrap break-all' => $wrap,
        'overflow-x-auto' => ! $wrap,
    ])><code x-ref="code">{{ $code }}</code></pre>
    <button type="button" x-on:click="copy()"
        class="absolute end-2 top-2 inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium transition {{ $buttonClasses }}">
        <svg x-show="!copied" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25" />
        </svg>
        <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
        <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))">{{ __('Copy') }}</span>
    </button>
</div>
