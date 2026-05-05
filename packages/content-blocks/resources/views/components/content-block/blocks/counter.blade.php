@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $counters = is_array($meta['counters'] ?? null) ? $meta['counters'] : [];
@endphp

<section
    {{ $attributes->merge(['class' => 'content-block content-block-counter']) }}
>
    @if ($title || $summary)
        <header class="mb-8 text-center">
            @if ($title)
                <h2 class="text-3xl font-bold">{{ $title }}</h2>
            @endif

            @if ($summary)
                <div class="mx-auto mt-3 max-w-3xl text-lg opacity-80">
                    {!! $summary !!}
                </div>
            @endif
        </header>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($counters as $counter)
            <article
                class="rounded-lg border border-slate-200 bg-white p-6 text-center"
            >
                @if (filled($counter['icon'] ?? null))
                    <x-capell::icon
                        :icon="$counter['icon']"
                        class="mx-auto mb-4 h-8 w-8 text-slate-500"
                    />
                @endif

                <p class="text-4xl font-bold">
                    {{ ($counter['prefix'] ?? '') . ($counter['value'] ?? '') . ($counter['suffix'] ?? '') }}
                </p>
                <h3 class="mt-2 font-semibold">
                    {{ $counter['label'] ?? '' }}
                </h3>

                @if (filled($counter['description'] ?? null))
                    <p class="mt-2 text-sm text-slate-600">
                        {{ $counter['description'] }}
                    </p>
                @endif
            </article>
        @endforeach
    </div>
</section>
