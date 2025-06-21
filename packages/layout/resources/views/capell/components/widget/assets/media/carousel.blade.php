<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Models\Media;
    use Capell\Frontend\Facades\Frontend;
@endphp

@props([
    'carouselAlign' => 'center',
    'carouselArrows' => true,
    'carouselAuto' => true,
    'carouselAutoDelay' => 3000,
    'carouselButtonClass' => 'text-primary hover:enabled:bg-primary active:enabled:bg-primary absolute top-0 z-10 flex h-full w-10 max-w-[8vw] cursor-pointer items-center justify-center hover:enabled:text-white active:enabled:text-white',
    'carouselDrag' => true,
    'carouselLoop' => true,
    'carouselPagination' => false,
    'carouselWheel' => true,
    'carouselSpacing' => '1rem',
    'colorScheme' => $widget->meta['color_scheme'] ?? 'dark',
    'container',
    'containerKey',
    'hideContent' => $widgetData['meta']['hide_content'] ?? false,
    'loop',
    'theme' => Frontend::getTheme(),
    'total' => $widget->assets->isNotEmpty() ? $widget->assets->count() : 1,
    'widget',
])
<x-capell::widget.wrapper
    class="widget-media-carousel"
    :$container
    :$containerKey
    :index="$loop->index"
    :$widget
>
    @if ($widget->translation && ! $hideContent)
        <div class="container mb-8">
            <x-capell::content
                :compact="true"
                :$containerKey
                :content="$widget->translation->content"
                :contents="$widget->translation->content ? null : $widget->translation->contents"
                :color-scheme="$colorScheme"
                :title="$widget->translation->title"
                :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            />
        </div>
    @endif

    <div
        wire:ignore
        data-auto="{{ (int) $carouselAuto }}"
        data-loop="{{ (int) $carouselLoop }}"
        data-delay="{{ $carouselAutoDelay }}"
        data-align="{{ $carouselAlign }}"
        data-drag="{{ (int) $carouselDrag }}"
        data-wheel="{{ (int) $carouselWheel }}"
        @class(['relative', 'embla' => $total > 1])
        @style(["--carousel-spacing:{$carouselSpacing}" => $carouselSpacing])
    >
        <div class="embla__viewport w-full overflow-hidden px-8">
            <div
                @class([
                    'embla__container flex touch-pan-y flex-row',
                    'ml-[calc(var(--carousel-spacing)*-1)]' => $carouselSpacing,
                ])
            >
                @foreach ($widget->assets as $widgetAsset)
                    @php
                        $resource = $widgetAsset->asset;
                        $media = $widgetAsset->image ?? ($resource instanceof Media ? $resource : $resource->media);
                        $width = 400;
                    @endphp

                    @continue(! $media?->width)

                    @php
                        $height = floor($width * ($media->height / $media->width));
                    @endphp

                    <div
                        @class([
                            'embla__slide transform-opacity [:not(.is-snapped)]:opacity-20 group h-64 min-w-0 max-w-full shrink-0 grow-0 basis-auto select-none text-white duration-200 ease-in-out',
                            'ml-[var(--carousel-spacing)]' => $carouselSpacing,
                        ])
                    >
                        <x-capell::media
                            :class="'embla__slide__img h-64 bg-gray-50 shadow'.($theme->withDarkMode ? 'dark:bg-gray-900' : '')"
                            :$loop
                            :media="$media"
                            :srcset="['400w', '200w']"
                            :width="$width"
                            :height="$height"
                            sizes="(max-width: 640px) 80vw, 20w"
                            lightbox="true"
                        />
                    </div>
                @endforeach
            </div>
        </div>

        @if ($total > 1)
            <div
                class="embla__controls pointer-events-none absolute inset-0 z-50"
            >
                @if ($carouselArrows)
                    <button
                        aria-label="{{ __('capell-frontend::generic.previous') }}"
                        @class([
                            'embla__button embla__button--prev [:is(.embla__button--disabled)]:hidden hover:text-primary focus:text-primary pointer-events-auto absolute bottom-0 left-0 top-0',
                            $carouselButtonClass,
                        ])
                        disabled
                    >
                        @svg('heroicon-o-chevron-left', 'embla__button__svg h-12 w-10')
                    </button>
                    <button
                        aria-label="{{ __('capell-frontend::generic.next') }}"
                        @class([
                            'embla__button embla__button--next [:is(.embla__button--disabled)]:hidden hover:text-primary focus:text-primary pointer-events-auto absolute bottom-0 right-0 top-0',
                            $carouselButtonClass,
                        ])
                        disabled
                    >
                        @svg('heroicon-o-chevron-right', 'embla__button__svg h-12 w-10')
                    </button>
                @endif

                @if ($carouselPagination)
                    <div
                        class="embla__dots pointer-events-auto hidden select-none justify-center gap-x-3 pt-4 md:flex"
                    ></div>
                @endif
            </div>
        @endif
    </div>
</x-capell::widget.wrapper>

<?php
