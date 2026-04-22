<?php

declare(strict_types=1);

?>

@props([
    'key',
    'total',
    'carouselAlign' => 'center',
    'carouselArrows' => false,
    // kept old name for backward compatibility but prefer carouselButtonClass
    'carouselArrowClass' => 'hover:text-primary focus:text-primary absolute bottom-0 top-0 flex w-10 cursor-pointer items-center justify-center text-center hover:bg-white/50 disabled:opacity-50',
    'carouselButtonClass' => null,
    'carouselAutoPlay' => false,
    'carouselAutoDelay' => 8000,
    'carouselDisableOnInteraction' => true,
    'carouselDrag' => true,
    'carouselEffect' => 'slide',
    'carouselFade' => false,
    'carouselLoop' => false,
    'carouselPagination' => true,
    'carouselPauseOnHover' => true,
    'carouselRewind' => false,
    'carouselSpeed' => 300,
    'carouselTouch' => null,
    'carouselWheel' => true,
])
@php
    $carouselId = sprintf('hero-carousel-%s', $key);
@endphp

<div
    data-auto="{{ (int) $carouselAutoPlay }}"
    data-carousel="1"
    data-carousel-align="{{ $carouselAlign }}"
    data-carousel-autoplay="{{ (int) $carouselAutoPlay }}"
    data-carousel-autoplay-delay="{{ $carouselAutoDelay }}"
    data-carousel-disable-on-interaction="{{ (int) $carouselDisableOnInteraction }}"
    data-carousel-drag="{{ (int) $carouselDrag }}"
    data-carousel-effect="{{ $carouselEffect }}"
    data-carousel-id="{{ $carouselId }}"
    data-loop="{{ (int) $carouselLoop }}"
    data-delay="{{ $carouselAutoDelay }}"
    data-align="{{ $carouselAlign }}"
    data-drag="{{ (int) $carouselDrag }}"
    data-carousel-loop="{{ (int) $carouselLoop }}"
    data-carousel-navigation="{{ (int) $carouselArrows }}"
    data-carousel-pagination="{{ (int) $carouselPagination }}"
    data-carousel-pause-on-hover="{{ (int) $carouselPauseOnHover }}"
    data-carousel-rewind="{{ (int) $carouselRewind }}"
    data-carousel-speed="{{ $carouselSpeed }}"
    data-carousel-watch-overflow="1"
    data-carousel-wheel="{{ (int) $carouselWheel }}"
    data-wheel="{{ (int) $carouselWheel }}"
    data-fade="{{ (int) $carouselFade }}"
    @if ($carouselTouch !== null)
        data-carousel-touch="{{ (int) $carouselTouch }}"
    @endif
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
        <div
            class="swiper-controls"
            data-carousel-controls="{{ $carouselId }}"
        >
            @if ($carouselArrows)
                <button
                    class="{{ $carouselButtonClass ?? $carouselArrowClass }} swiper-button-prev left-6"
                    aria-label="{{ __('generic.previous') }}"
                >
                    @svg('heroicon-o-chevron-left', 'swiper-button-svg')
                </button>
                <button
                    class="{{ $carouselButtonClass ?? $carouselArrowClass }} swiper-button-next right-6"
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
