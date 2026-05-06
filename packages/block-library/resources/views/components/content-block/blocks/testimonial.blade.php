@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

<figure
    {{ $attributes->merge(['class' => 'content-block content-block-testimonial']) }}
>
    <blockquote class="text-2xl font-medium leading-relaxed">
        “{{ $meta['quote'] ?? strip_tags((string) $summary) }}”
    </blockquote>
    <figcaption class="mt-6">
        <p class="font-semibold">{{ $meta['author'] ?? $title }}</p>

        @if (filled($meta['role'] ?? null))
            <p class="text-sm text-slate-500">{{ $meta['role'] }}</p>
        @endif
    </figcaption>
</figure>
