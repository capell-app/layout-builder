<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;

        $language = Frontend::language();
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

<div
    class="rounded-2xl bg-white p-6 text-center text-gray-800 shadow-md dark:bg-gray-900/60 dark:text-white"
>
    @if ($image)
        <img
            class="mx-auto mb-4 h-36 w-64 object-cover"
            src="{{ $image->getUrl('thumb') }}"
            alt="{{ e(strip_tags($title ?? '')) }}"
        />
    @endif

    @if ($title)
        <h3 class="text-xl font-semibold">{!! $title !!}</h3>
    @endif

    @if (! empty($meta['position']))
        <p class="text-sm">{{ $meta['position'] }}</p>
    @endif

    @if (filled($summary))
        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
            {!! $summary !!}
        </div>
    @endif
</div>

<?php
