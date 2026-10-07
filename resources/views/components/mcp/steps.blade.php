@props(['steps', 'start' => 1])

<ol {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @foreach ($steps as $step)
        <li class="flex gap-3">
            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-800">{{ $start + $loop->index }}</span>
            <div class="min-w-0 flex-1 space-y-2">
                <p class="text-sm leading-relaxed text-slate-700">{{ $step['text'] }}</p>
                @isset($step['code'])
                    <x-mcp.code :code="$step['code']" />
                @endisset
            </div>
        </li>
    @endforeach
</ol>
