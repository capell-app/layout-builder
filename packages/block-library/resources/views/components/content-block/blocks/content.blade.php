@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

<section
    {{ $attributes->merge(['class' => 'content-block content-block-content']) }}
>
    @if ($title)
        <h2 class="mb-4 text-3xl font-bold">{{ $title }}</h2>
    @endif

    @if ($summary)
        <div class="prose max-w-none text-lg">
            {!! $summary !!}
        </div>
    @endif
</section>
