<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;

    $language = Frontend::getLanguage();
@endphp

@props([
    'color' => null,
    'icon' => null,
    'image' => null,
    'linkText' => null,
    'loop' => null,
    'meta' => [],
    'summary',
    'tags' => null,
    'title',
    'url' => null,
])

<div class="rounded-2xl bg-gray-50 p-6 text-center shadow-md">
    @if ($image)
        <img
            class="mx-auto mb-4 h-36 w-64 object-cover"
            src="{{ $image->url }}"
            alt="{{ e(strip_tags($title ?? '')) }}"
        />
    @endif

    @if ($title)
        <h3 class="text-xl font-semibold text-gray-800">{!! $title !!}</h3>
    @endif

    @if (! empty($meta['position']))
        <p class="text-sm text-indigo-600">{{ $meta['position'] }}</p>
    @endif

    @if (filled($summary))
        <div class="mt-2 text-sm text-gray-600">
            {!! $summary !!}
        </div>
    @endif
</div>

<?php
