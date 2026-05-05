@props(['asset', 'linkText' => null, 'meta' => [], 'summary' => null, 'title' => null, 'url' => null])

<section
    {{ $attributes->merge(['class' => 'content-block content-block-call-to-action text-center']) }}
>
    @if ($title)
        <h2 class="text-3xl font-bold">{{ $title }}</h2>
    @endif

    @if ($summary)
        <div class="mx-auto mt-4 max-w-3xl text-lg opacity-80">
            {!! $summary !!}
        </div>
    @endif

    @if ($url && $linkText)
        <a
            href="{{ $url }}"
            class="bg-primary mt-6 inline-flex rounded px-5 py-3 font-semibold text-white"
        >
            {{ $linkText }}
        </a>
    @endif
</section>
