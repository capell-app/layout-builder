<?php

declare(strict_types=1);

?>

@props([
    'key',
    'total',
    'carouselArrows' => false,
    'carouselArrowClass' => 'hover:text-primary focus:text-primary absolute bottom-0 top-0 flex w-10 cursor-pointer items-center justify-center text-center hover:bg-white/50 disabled:opacity-50',
    'carouselAuto' => false,
    'carouselAutDelay' => 8000,
    'carouselLoop' => false,
    'carouselPagination' => true,
    'carouselSpacing' => true,
    'carouselType' => 'slide',
])

<div
    data-auto="{{ (int) $carouselAuto }}"
    data-loop="{{ (int) $carouselLoop }}"
    data-delay="{{ $carouselAutoDelay }}"
    @class([
        'grid min-h-full',
        'embla relative' => $total > 1,
        'embla__fade' => $carouselType === 'fade',
    ])
>
    <div class="embla__viewport min-h-full w-full overflow-hidden">
        <div
            @class([
                'embla__container flex min-h-full touch-pan-y touch-pinch-zoom select-none',
                '-ml-4' => $carouselSpacing && $carouselType === 'slide',
            ])
        >
            {{ $slot }}
        </div>
    </div>
    @if ($total > 1)
        <div class="embla__controls">
            @if ($carouselArrows)
                <button
                    class="{{ $carouselArrowClass }} embla__button embla__button--prev left-6"
                    aria-label="{{ __('generic.previous') }}"
                    disabled=""
                >
                    @svg('heroicon-o-chevron-left', 'embla__button__svg')
                </button>
                <button
                    class="{{ $carouselArrowClass }} embla__button embla__button--next right-6"
                    aria-label="{{ __('generic.next') }}"
                    disabled=""
                >
                    @svg('heroicon-o-chevron-right', 'embla__button__svg')
                </button>
            @endif

            @if ($carouselPagination)
                <div
                    class="embla__dots absolute bottom-8 left-0 right-0 z-10 flex select-none justify-center gap-x-3"
                    wire:ignore
                ></div>
            @endif
        </div>
    @endif
</div>

<?php
