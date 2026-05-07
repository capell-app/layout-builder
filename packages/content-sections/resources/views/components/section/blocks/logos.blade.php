@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $logos = is_array($meta['logos'] ?? null) ? $meta['logos'] : [];
    $columns = (string) ($meta['columns'] ?? '4');
@endphp

<section
    {{ $attributes->merge(['class' => 'section section-logos']) }}
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

    <div
        @class(['grid gap-4', 'grid-cols-2' => true, 'md:grid-cols-3' => $columns === '3', 'md:grid-cols-4' => $columns === '4'])
    >
        @foreach ($logos as $logo)
            <a
                href="{{ $logo['url'] ?? '#' }}"
                class="flex min-h-24 items-center justify-center rounded-lg border border-slate-200 bg-white px-6 text-center text-lg font-semibold text-slate-600"
            >
                {{ $logo['name'] ?? '' }}
            </a>
        @endforeach
    </div>
</section>
