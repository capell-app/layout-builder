<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;
    use Spatie\Image\Image;
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    $theme = Frontend::theme();
@endphp

@props([
    'carouselAlign' => 'center',
    'carouselArrows' => true,
    'carouselAuto' => true,
    'carouselAutoDelay' => 5000,
    'carouselButtonClass' => 'hover:bg-primary focus:bg-primary pointer-events-auto bg-white/80 shadow-md transition hover:text-white focus:text-white disabled:pointer-events-none disabled:opacity-50',
    'carouselDrag' => true,
    'carouselLoop' => true,
    'carouselPagination' => false,
    'carouselWheel' => true,
    'color' => $widget->meta['color'] ?? 'dark',
    'container',
    'containerKey',
    'containerWidth' => null,
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'loop',
    'rounded' => $theme->meta['rounded_images'] ?? false,
    'total' => $widget->assets->isNotEmpty() ? $widget->assets->count() : 1,
    'widget',
])
<x-capell-layout::widget.wrapper
    class="widget-media-carousel"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    @if (($widget->translation && ($widget->translation->title || $widget->translation->content))
         || ($showPageTitle && $page->translation->title)
         || ($showPageContent && $page->translation->content))
        <div class="container mb-8">
            <x-capell::content
                :compact="true"
                :content="$widget->translation->content ?? ($showPageContent ? $page->translation->content : null)"
                :content-type="$widget->translation->content ? $widget->type->content_structure : ($showPageContent ? $page->type->content_structure : null)"
                :color="$color"
                :muted="in_array($containerKey, $theme->secondary_containers)"
                :title="$widget->translation->title ?? ($showPageTitle ? $page->translation->title : null)"
                :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
                :heading-style="($widget->meta['heading_style'] ?? null) ?: ($widget->type->meta['heading_style'] ?? null)"
                :heading-tag="$showPageTitle ? 'h1' : null"
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
        data-breakpoint='{
            "992": {
                "slidesPerView": "auto",
                "spaceBetween": 36
            },
            "768": {
                "slidesPerView": "auto",
                "spaceBetween": 24
            },
            "320": {
                "slidesPerView": 1,
                "spaceBetween": 0
            }
        }'
        @class(['relative py-10', 'swiper' => $total > 1])
        style="--swiper-navigation-sides-offset: 0"
    >
        <div class="swiper-wrapper w-full">
            @foreach ($widget->assets as $widgetAsset)
                {{-- format-ignore-start --}}
                @php
                    /** @var Media|null $media */
                    $media = $widgetAsset->media->first() ?: $widgetAsset->asset->image;
                    if (! $media) {
                        throw new RuntimeException('Image not found for WidgetAsset: ' . $widgetAsset->asset_type . ' ' . $widgetAsset->id);
                    }

                    $imageWidth = $media->getCustomProperty('width');
                    $imageHeight = $media->getCustomProperty('height');

                    if (Str::startsWith($media->mime_type, 'image/') && (! $imageWidth || ! $imageHeight)) {
                        $image = Image::load($media->getPath());

                        $imageWidth = $image->getWidth();
                        $imageHeight = $image->getHeight();
                    } else {
                        $imageHeight = 400;
                        $imageWidth = 400;
                    }

                    $width = 400;
                    $height = floor($width * ($imageHeight / $imageWidth));
                @endphp
                {{-- format-ignore-end --}}
                <div
                    @class([
                        'swiper-slide widget-media-item group relative h-64 overflow-hidden text-center text-white',
                        'rounded-lg' => $rounded,
                    ])
                    tabindex="0"
                >
                    <x-capell::media
                        :class="'swiper-slide-img object-cover h-64 mx-auto bg-gray-50 transition-transform duration-300 group-hover:scale-105 group-focus:scale-105' . ($theme->withDarkMode ? ' dark:bg-gray-900' : '')"
                        :$loop
                        :media="$media"
                        :alt="$widgetAsset->asset->translation->label"
                        :width="$width"
                        :height="$height"
                        sizes="(max-width: 640px) 80vw, 20w"
                        lightbox="true"
                        rounded="true"
                    />
                    @if ($widgetAsset->asset->translation?->title)
                        <div
                            class="pointer-events-none absolute inset-x-0 bottom-0 flex translate-y-full transform items-center justify-center break-words bg-gray-600/75 px-2 py-4 text-sm font-medium leading-none leading-tight text-white opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100 group-focus:translate-y-0 group-focus:opacity-100"
                        >
                            {{ $widgetAsset->asset->translation->title }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($total > 1)
            <div
                class="swiper-controls pointer-events-none absolute inset-0 z-50 flex items-center justify-between"
            >
                @if ($carouselArrows)
                    <button
                        aria-label="{{ __('capell-frontend::generic.previous') }}"
                        @class([
                            'swiper-button-prev rounded-r-md',
                            $carouselButtonClass,
                        ])
                        style="width: 50px; height: 60px; margin-top: -30px"
                    ></button>
                    <button
                        aria-label="{{ __('capell-frontend::generic.next') }}"
                        @class([
                            'swiper-button-next rounded-l-md',
                            $carouselButtonClass,
                        ])
                        style="width: 50px; height: 60px; margin-top: -30px"
                    ></button>
                @endif

                @if ($carouselPagination)
                    <div
                        class="swiper-pagination pointer-events-auto absolute bottom-2 left-1/2 flex -translate-x-1/2 select-none justify-center pt-4"
                        wire:ignore
                    ></div>
                @endif
            </div>
        @endif
    </div>
</x-capell-layout::widget.wrapper>

<?php
