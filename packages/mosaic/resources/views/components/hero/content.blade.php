<?php

declare(strict_types=1);

?>

@aware(['carouselArrows'])

@props([
    'color',
    'content_class' => 'hero-content prose max-w-none tracking-wider',
    'headingSize' => 'h1',
    'linkClass' => 'text-link hover:text-primary focus:text-primary font-medium focus:underline',
    'size' => 'md',
    'title' => '',
    'url' => '',
])
{{-- format-ignore-start --}}
<div
    {{
        $attributes->merge([
            'class' => implode(
                ' ',
                array_merge(
                    [$content_class],
                    array_keys(
                        array_filter([
                            'prose-invert' => $color === 'dark',
                            'ml-10' => $carouselArrows,
                        ]),
                    ),
                ),
            ),
        ])
    }}
    @if ($color === 'dark') style="--tw-prose-invert-body: #fff;" @endif
>
    @if ($title)
        <{{ $headingSize }}
            @class([
            'hero-heading leading-12 font-semibold text-balance lg:leading-14',
            'text-2xl md:text-4xl' => $size === 'sm',
            'text-3xl md:text-5xl' => $size === 'md',
            'text-4xl md:text-6xl' => $size === 'lg',
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

{{-- format-ignore-end --}}

<?php
