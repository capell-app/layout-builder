@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $stats = is_array($meta['stats'] ?? null) ? $meta['stats'] : [];
    $columns = (string) ($meta['columns'] ?? '4');
@endphp

<section
    {{ $attributes->merge(['class' => 'section section-stats']) }}
>
    @if ($title || $summary)
        <header class="mb-8">
            @if ($title)
                <h2 class="text-3xl font-bold">{{ $title }}</h2>
            @endif

            @if ($summary)
                <div class="mt-3 text-lg opacity-80">{!! $summary !!}</div>
            @endif
        </header>
    @endif

    <div
        @class(['grid gap-4', 'md:grid-cols-2' => $columns === '2', 'md:grid-cols-3' => $columns === '3', 'md:grid-cols-4' => $columns === '4'])
    >
        @foreach ($stats as $stat)
            <article class="rounded-lg bg-slate-100 p-5">
                <p class="text-3xl font-bold">{{ $stat['value'] ?? '' }}</p>
                <h3 class="mt-2 font-semibold">{{ $stat['label'] ?? '' }}</h3>

                @if (filled($stat['description'] ?? null))
                    <p class="mt-2 text-sm text-slate-600">
                        {{ $stat['description'] }}
                    </p>
                @endif
            </article>
        @endforeach
    </div>
</section>
