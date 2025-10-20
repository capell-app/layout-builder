<?php

declare(strict_types=1);

?>

@aware(['carouselArrows'])

@props([
    'colorScheme',
    'content_class' => 'prose xl:prose-lg max-w-none tracking-wider',
    'headingSize' => 'h1',
    'linkClass' => 'text-link hover:text-primary focus:text-primary font-medium focus:underline',
    'size' => 'md',
    'title' => '',
    'url' => '',
])
<div
    {{
        $attributes->merge([
            'class' => implode(
                ' ',
                array_merge(
                    [$content_class],
                    array_keys(
                        array_filter([
                            'prose-invert' => $colorScheme === 'dark',
                            'ml-10' => $carouselArrows,
                        ]),
                    ),
                ),
            ),
        ])
    }}
    @if ($colorScheme === 'dark') style="--tw-prose-invert-body: #fff;" @endif
>
    @if ($title)
        <{{ $headingSize }}
            @class([
                '!my-4',
                'text-2xl md:text-4xl' => $size === 'sm',
                'text-3xl md:text-5xl' => $size === 'md',
                'text-4xl md:text-6xl' => $size === 'lg',
                'leading-12 lg:leading-14 text-balance',
            ])
        >
            @if ($url)
                <a
                    class="{{ $linkClass }}"
                    href="{{ $url }}"
                    wire:navigate
                >
                    {!! $title !!}
                </a>
            @else
                {!! $title !!}
            @endif
        </{{ $headingSize }}>
    @endif

    {{ $slot }}
</div>

<?php
