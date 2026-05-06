@props(['asset', 'meta' => [], 'summary' => null, 'title' => null, 'url' => null])

@php
    $alignment = $meta['alignment'] ?? 'center';
@endphp

<section
    @class([
        'content-block content-block-hero rounded-lg bg-slate-950 p-10 text-white',
        'text-left' => $alignment === 'start',
        'text-center' => $alignment === 'center',
        'text-right' => $alignment === 'end',
        $attributes->get('class'),
    ])
>
    @if ($title)
        <h1 class="text-5xl font-bold tracking-tight">{{ $title }}</h1>
    @endif

    @if ($summary)
        <div class="mx-auto mt-5 max-w-3xl text-xl text-slate-200">
            {!! $summary !!}
        </div>
    @endif

    @if ($url)
        <a
            href="{{ $url }}"
            class="mt-8 inline-flex rounded bg-white px-5 py-3 font-semibold text-slate-950"
        >
            {{ __('capell-block-library::button.read_more') }}
        </a>
    @endif
</section>
