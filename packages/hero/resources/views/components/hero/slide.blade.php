<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;
@endphp

@props([
    'backgroundAttachment' => '',
    'backgroundColor' => '',
    'backgroundImage' => null,
    'backgroundOverlay' => null,
    'backgroundPosition' => 'center',
    'backgroundRepeat' => 'no-repeat',
    'backgroundSize' => 'cover',
    'color' => 'dark',
    'containerClass' => '',
    'first' => false,
    'height' => '',
    'index',
    'slideBgImgClass' => '',
    'title' => null,
])
{{-- format-ignore-start --}}
<div
    {{
        $attributes->class([
            'swiper-slide hero-item relative',
            'swiper-slide-selected' => $first,
        ])
    }}
    data-index="{{ $index }}"
>
    <div
        @class([
            'swiper-slide-inner relative flex w-full min-h-full',
            ...(
                ! $backgroundColor && $color
                ? [
                    'bg-gradient-to-t',
                    'from-gray-600/60 to-gray-800/70 dark:from-gray-800/80 dark:to-gray-900/80' => $color === 'dark',
                    'from-black/10 to-gray-100/60 dark:from-gray-800/80 dark:to-gray-900/80' => $color === 'light',
                ]
                : []
            ),
        ])
        @style([
              "background-color: {$backgroundColor}" => $backgroundColor,
            ])
        }}
    >
        @if ($backgroundImage)
            <x-capell::media
                format="webp"
                :media="$backgroundImage"
                :alt="$title"
                height="auto"
                :loading="$first ? 'eager' : 'lazy'"
                :class="
                    Illuminate\Support\Arr::toCssClasses([
                        'hero-bg-img inset-0 h-full w-full pointer-events-none mix-blend-exclusion',
                        $slideBgImgClass ?? '',
                        $backgroundAttachment !== 'fixed' ? 'absolute' : '',
                        $backgroundAttachment === 'fixed' ? 'object-fixed' : '',
                        $backgroundSize === 'contain' ? 'object-contain' : '',
                        $backgroundSize === 'cover' ? 'object-cover' : '',
                        $backgroundPosition === 'center' ? 'object-center' : '',
                        $backgroundPosition === 'top' ? 'object-top' : '',
                        $backgroundPosition === 'right' ? 'object-right' : '',
                        $backgroundPosition === 'bottom' ? 'object-bottom' : '',
                        $backgroundPosition === 'left' ? 'object-left' : '',
                    ])
                "
            />
        @endif

        @if ($backgroundOverlay)
            <div
                class="pointer-events-none absolute inset-0 bg-black/50 mix-blend-multiply dark:bg-gray-900/60"
            ></div>
        @endif

        @if ($slot->isNotEmpty())
            <div @class(['relative grid w-full', $containerClass])>
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
{{-- format-ignore-end --}}

<?php
