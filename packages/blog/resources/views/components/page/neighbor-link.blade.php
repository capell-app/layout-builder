<?php

declare(strict_types=1);

?>

@props([
    'neighbor' => null,
    'neighborPage' => null,
    'withSummary' => true,
    'withImage' => true,
])
<a
    href="{{ $neighborPage->pageUrl->full_url }}"
    title="{{ htmlspecialchars(strip_tags($neighborPage->translation->title)) }}"
    {{
        $attributes->class([
            'neighbor-link hover:text-primary focus:text-primary group flex max-w-[50%] items-center gap-x-4 gap-y-3 py-6 md:py-3 lg:gap-x-4',
            'neighbor-link-next ml-auto justify-end text-right md:pl-10' => $neighbor === 'next',
            'neighbor-link-prev md:pr-10' => $neighbor === 'previous',
        ])
    }}
    wire:navigate
>
    @if ($neighbor === 'previous')
        @svg('heroicon-s-chevron-left', 'group-hover:text-primary relative h-8 w-8 shrink-0 text-gray-400')
    @endif

    @capture($imageContent)
        @if ($withImage && $neighborPage->image)
            <x-capell::media
                :media="$neighborPage->image"
                fit="crop"
                :width="200"
                @class([
                    'h-full w-auto max-w-[5rem] object-cover object-center group-hover:opacity-85',
                    'rounded' => $theme->meta['rounded_images'] ?? false,
                ])
                loading="lazy"
            />
        @endif
    @endcapture

    {{ $neighbor === 'previous' ? $imageContent() : '' }}

    <span class="flex flex-col">
        <span
            class="text-link group-hover:text-primary line-clamp-1 text-base font-medium text-gray-600"
        >
            {{ strip_tags($neighborPage->translation->label) }}
        </span>
        @if ($withSummary && $neighborPage->translation->summary)
            <span class="mt-0.5 line-clamp-2 break-words text-sm text-gray-500">
                {!! strip_tags($neighborPage->translation->summary) !!}
            </span>
        @endif
    </span>

    {{ $neighbor === 'next' ? $imageContent() : '' }}

    @if ($neighbor === 'next')
        @svg('heroicon-s-chevron-right', 'group-hover:text-primary relative h-8 w-8 shrink-0 text-gray-400')
    @endif
</a>

<?php
