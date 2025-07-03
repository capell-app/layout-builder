<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;
@endphp

@aware([
    'carouselArrows' => '',
])

@props([
    'backgroundColor' => '',
    'backgroundImage' => null,
    'backgroundPosition' => 'center',
    'backgroundSize' => 'cover',
    'backgroundAttachment' => '',
    'backgroundRepeat' => 'no-repeat',
    'backgroundOverlay' => null,
    'carouselSpacing' => true,
    'carouselType' => 'slide',
    'colorScheme' => 'dark',
    'container_class' => 'container',
    'slideBgImgClass' => '',
    'first' => false,
    'height' => '',
    'theme' => Frontend::getTheme(),
])
<div
    {{
        $attributes->class([
            'embla__slide min-h-full w-full shrink-0 basis-full select-none',
            'pl-4' => $carouselSpacing && $carouselType === 'slide',
            'embla__slide--selected' => $first,
        ])
    }}
>
    <div
        {{
            $attributes->class([
                'embla__slide_inner relative flex min-h-full',
                ...(
                    ! $backgroundColor && $colorScheme
                    ? [
                        'bg-gradient-to-t',
                        'from-gray-600/60 to-gray-800/70 dark:from-gray-800/80 dark:to-gray-900/80' => $colorScheme === 'dark',
                        'from-black/10 to-gray-100/60 dark:from-gray-800/80 dark:to-gray-900/80' => $colorScheme === 'light',
                    ]
                    : []
                ),
            ])
                ->style([
                    "background-color: $backgroundColor" => $backgroundColor,
                ])
        }}
    >
        @if ($backgroundImage)
            <x-dynamic-component
                format="webp"
                component="capell::media.background-glider"
                curation="hero"
                :media="$backgroundImage"
                :srcset="['1680w', '1024w', '640w']"
                height="auto"
                :loading="$first ? 'eager' : 'lazy'"
                {{-- format-ignore-start --}}
                :class="
                    Illuminate\Support\Arr::toCssClasses([
                        'inset-0 h-full w-full pointer-events-none mix-blend-exclusion',
                        $slideBgImgClass ?? '',
                        $backgroundAttachment !== 'scroll' ? 'absolute' : '',
                        $backgroundAttachment === 'scroll' ? 'absolute bg-scroll' : '',
                        $backgroundAttachment === 'fixed' ? 'bg-fixed' : '',
                        $backgroundSize === 'contain' ? 'bg-contain' : '',
                        $backgroundSize === 'cover' ? 'bg-cover' : '',
                        $backgroundRepeat === 'repeat' ? 'bg-repeat' : '',
                        $backgroundRepeat === 'no-repeat' ? 'bg-no-repeat' : '',
                        $backgroundRepeat === 'repeat-x' ? 'bg-repeat-x' : '',
                        $backgroundRepeat === 'repeat-y' ? 'bg-repeat-y' : '',
                        $backgroundPosition === 'center' ? 'bg-center' : '',
                        $backgroundPosition === 'top' ? 'bg-top' : '',
                        $backgroundPosition === 'right' ? 'bg-right' : '',
                        $backgroundPosition === 'bottom' ? 'bg-bottom' : '',
                        $backgroundPosition === 'left' ? 'bg-left' : '',
                    ])
                "
                {{-- format-ignore-end --}}
            />
        @endif

        @if ($backgroundOverlay)
            <div
                class="pointer-events-none absolute inset-0 bg-black/50 mix-blend-multiply dark:bg-gray-900/60"
            ></div>
        @endif

        @if ($slot->isNotEmpty())
            <div @class(['relative', $container_class])>
                {{ $slot }}
            </div>
        @endif
    </div>
</div>

<?php
