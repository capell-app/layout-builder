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
    'carouselType' => 'slide',
])

<div
    data-auto="{{ (int) $carouselAuto }}"
    data-loop="{{ (int) $carouselLoop }}"
    data-delay="{{ $carouselAutoDelay }}"
    data-fade="{{ $carouselType === 'fade' }}"
    style="
        --swiper-pagination-bottom: 1.5rem;
        --swiper-pagination-bullet-inactive-color: #fff;
    "
    @class([
        'grid min-h-full w-full',
        'swiper relative' => $total > 1,
    ])
>
    <div class="swiper-wrapper min-h-full w-full">
        {{ $slot }}
    </div>
    @if ($total > 1)
        <div class="swiper-controls">
            @if ($carouselArrows)
                <button
                    class="{{ $carouselArrowClass }} swiper-button-prev left-6"
                    aria-label="{{ __('generic.previous') }}"
                >
                    @svg('heroicon-o-chevron-left', 'swiper-button-svg')
                </button>
                <button
                    class="{{ $carouselArrowClass }} swiper-button-next right-6"
                    aria-label="{{ __('generic.next') }}"
                >
                    @svg('heroicon-o-chevron-right', 'swiper-button-svg')
                </button>
            @endif

            @if ($carouselPagination)
                <div
                    class="swiper-pagination absolute bottom-8 left-0 right-0 z-10 flex select-none justify-center"
                    wire:ignore
                ></div>
            @endif
        </div>
    @endif
</div>

<?php
