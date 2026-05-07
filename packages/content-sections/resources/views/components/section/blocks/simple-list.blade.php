@props([
    'asset',
    'heading' => null,
    'itemKey',
    'meta' => [],
    'summary' => null,
    'title' => null,
])

@php
    $items = is_array($meta[$itemKey] ?? null) ? $meta[$itemKey] : [];
@endphp

<section
    {{ $attributes->merge(['class' => 'section section-' . $itemKey]) }}
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

    <div class="grid gap-4">
        @foreach ($items as $item)
            <article class="rounded border border-gray-200 p-5">
                @if ($heading && filled($item[$heading] ?? null))
                    <h3 class="text-lg font-semibold">
                        {{ $item[$heading] }}
                    </h3>
                @endif

                @if (filled($item['label'] ?? null) && $heading !== 'label')
                    <h3 class="text-lg font-semibold">{{ $item['label'] }}</h3>
                @endif

                @if (filled($item['value'] ?? null))
                    <p class="text-3xl font-bold">
                        {{ ($item['prefix'] ?? '') . $item['value'] . ($item['suffix'] ?? '') }}
                    </p>
                @endif

                @if (filled($item['description'] ?? null))
                    <p class="mt-2 opacity-80">{{ $item['description'] }}</p>
                @endif

                @if (filled($item['content'] ?? null))
                    <div class="prose mt-3 max-w-none">
                        {!! $item['content'] !!}
                    </div>
                @endif

                @if (filled($item['answer'] ?? null))
                    <div class="prose mt-3 max-w-none">
                        {!! $item['answer'] !!}
                    </div>
                @endif
            </article>
        @endforeach
    </div>
</section>
