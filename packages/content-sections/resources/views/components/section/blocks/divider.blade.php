@props(['asset', 'meta' => []])

<div
    {{ $attributes->merge(['class' => 'section section-divider py-8']) }}
>
    @if (($meta['style'] ?? 'line') === 'dots')
        <div class="text-center tracking-widest">...</div>
    @elseif (($meta['style'] ?? 'line') === 'line')
        <hr class="border-gray-200" />
    @endif
</div>
