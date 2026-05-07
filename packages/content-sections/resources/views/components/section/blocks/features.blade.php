@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $features = is_array($meta['features'] ?? null) ? $meta['features'] : [];
    $columns = (string) ($meta['columns'] ?? '3');
@endphp

<section
    {{ $attributes->merge(['class' => 'section section-features']) }}
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
        @class(['grid gap-5', 'md:grid-cols-2' => $columns === '2', 'md:grid-cols-3' => $columns === '3', 'md:grid-cols-4' => $columns === '4'])
    >
        @foreach ($features as $feature)
            <article class="rounded-lg border border-slate-200 bg-white p-6">
                @if (filled($feature['icon'] ?? null))
                    {!! svg($feature['icon'], 'mb-4 h-7 w-7 text-slate-500')->toHtml() !!}
                @endif

                <h3 class="text-lg font-semibold">
                    {{ $feature['heading'] ?? '' }}
                </h3>

                @if (filled($feature['description'] ?? null))
                    <p class="mt-2 text-slate-600">
                        {{ $feature['description'] }}
                    </p>
                @endif

                @if (filled($feature['url'] ?? null))
                    <a
                        href="{{ $feature['url'] }}"
                        class="mt-4 inline-flex font-semibold text-slate-950 hover:underline"
                    >
                        {{ __('capell-content-sections::button.read_more') }}
                    </a>
                @endif
            </article>
        @endforeach
    </div>
</section>
