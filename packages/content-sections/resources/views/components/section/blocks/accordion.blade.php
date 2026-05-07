@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $items = is_array($meta['items'] ?? null) ? $meta['items'] : [];
    $firstOpen = (bool) ($meta['first_open'] ?? false);
@endphp

<section
    {{ $attributes->merge(['class' => 'section section-accordion']) }}
>
    @if ($title || $summary)
        <header class="mb-6">
            @if ($title)
                <h2 class="text-3xl font-bold">{{ $title }}</h2>
            @endif

            @if ($summary)
                <div class="mt-3 text-lg opacity-80">{!! $summary !!}</div>
            @endif
        </header>
    @endif

    <div
        class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white"
    >
        @foreach ($items as $item)
            <details
                class="group p-5"
                @if ($loop->first && $firstOpen) open @endif
            >
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold"
                >
                    <span>{{ $item['heading'] ?? '' }}</span>
                    <span
                        class="text-xl leading-none transition group-open:rotate-45"
                    >
                        +
                    </span>
                </summary>

                @if (filled($item['content'] ?? null))
                    <div class="prose mt-4 max-w-none text-slate-700">
                        {!! $item['content'] !!}
                    </div>
                @endif
            </details>
        @endforeach
    </div>
</section>
