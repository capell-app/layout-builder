<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Facades\CapellCore;
    use Capell\Frontend\Facades\Frontend;
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    $page = Frontend::page();
    $theme = Frontend::theme();
@endphp

@props([
    'align' => $widget->getMeta('align', 'center'),
    'carouselArrows' => (bool) $widget->getMeta('carousel_arrows', false),
    'carouselFade' => $widget->getMeta('carousel_fade', true),
    'carouselAutoPlay' => $widget->getMeta('carousel_auto_play', true),
    'carouselAutoDelay' => $widget->getMeta('carousel_auto_delay', 5000),
    'carouselDisableOnInteraction' => (bool) $widget->getMeta('carousel_disable_on_interaction', true),
    'carouselDrag' => (bool) $widget->getMeta('carousel_drag', false),
    'carouselEffect' => $widget->getMeta('carousel_effect', 'slide'),
    'carouselLoop' => $widget->getMeta('carousel_loop', true),
    'carouselPagination' => $widget->getMeta('carousel_pagination', true),
    'carouselPauseOnHover' => (bool) $widget->getMeta('carousel_pause_on_hover', true),
    'carouselRewind' => (bool) $widget->getMeta('carousel_rewind', false),
    'carouselSpeed' => (int) $widget->getMeta('carousel_speed', 300),
    'carouselTouch' => $widget->getMeta('carousel_touch'),
    'carouselWheel' => (bool) $widget->getMeta('carousel_wheel', false),
    'color' => $widget->getMeta('color', 'light'),
    'containerKey',
    'containerIndex',
    'containerWidth',
    'loop',
    'total' => $widget->assets->count(),
    'widget',
    'widgetIndex',
])
@php
    $carouselId = sprintf('testimonial-carousel-%s-%s', $widget->id ?? $widget->key, $loop->index);
    $carouselEffect = $carouselFade ? 'fade' : $carouselEffect;
@endphp

<x-capell-layout::widget.wrapper
    class="widget-assets widget-assets-testimonials"
    :$container
    :$containerKey
    :$containerWidth
    container-class="relative py-6 space-y-6 md:space-y-10 lg:py-16"
    :index="$loop->index"
    :$widget
>
    @if ($widget->translation)
        <x-capell::content
            :compact="true"
            :content="$widget->translation->content"
            :content-type="$widget->type->content_structure"
            :color="$color"
            :divider="$widget->getMeta('content_divider')"
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :title="$widget->translation->title"
            heading-weight="semibold"
            :text-align="$align"
            :heading-style="$widget->getMeta('heading_style')"
            class="mt-4"
        />
    @endif

    @if ($widget->assets->isNotEmpty())
        <div
            @class([
                'relative',
                'pb-4' => $total > 1,
            ])
            style="
                --swiper-pagination-bottom: auto;
                --swiper-pagination-top: 100%;
                --swiper-pagination-bullet-inactive-color: #fff;
            "
        >
            <div
                data-carousel="1"
                data-carousel-align="{{ $align }}"
                data-carousel-autoplay="{{ (int) $carouselAutoPlay }}"
                data-carousel-autoplay-delay="{{ $carouselAutoDelay }}"
                data-carousel-disable-on-interaction="{{ (int) $carouselDisableOnInteraction }}"
                data-carousel-drag="{{ (int) $carouselDrag }}"
                data-carousel-effect="{{ $carouselEffect }}"
                data-carousel-id="{{ $carouselId }}"
                data-carousel-loop="{{ (int) $carouselLoop }}"
                data-carousel-navigation="{{ (int) $carouselArrows }}"
                data-carousel-pagination="{{ (int) $carouselPagination }}"
                data-carousel-pause-on-hover="{{ (int) $carouselPauseOnHover }}"
                data-carousel-rewind="{{ (int) $carouselRewind }}"
                data-carousel-speed="{{ $carouselSpeed }}"
                data-carousel-watch-overflow="1"
                data-carousel-wheel="{{ (int) $carouselWheel }}"
                data-auto="{{ (int) $carouselAutoPlay }}"
                data-loop="{{ (int) $carouselLoop }}"
                data-delay="{{ $carouselAutoDelay }}"
                data-fade="{{ $carouselFade }}"
                @if ($carouselTouch !== null)
                    data-carousel-touch="{{ (int) $carouselTouch }}"
                @endif
                class="swiper grid h-full w-full"
            >
                <div class="swiper-wrapper h-full w-full">
                    @foreach ($widget->assets as $widgetAsset)
                        {{-- format-ignore-start --}}
                        @php
                            $title = '';
                            $content = '';
                            $media = $widgetAsset->media->first() ?: $widgetAsset->asset->image;

                            $position = $widgetAsset->asset->translation->getMeta('position', '');
                            $company = $widgetAsset->asset->translation->getMeta('company', '');

                            if (CapellCore::getAsset($widgetAsset->asset_type)->hasTranslations) {
                                $title = $widgetAsset->asset->translation?->title;
                                $content = $widgetAsset->asset->translation?->content;
                            }
                        @endphp
                        {{-- format-ignore-end --}}

                        <div
                            class="swiper-slide widget-testimonial-item"
                            itemscope
                            itemtype="https://schema.org/Review"
                        >
                            <div
                                @class([
                                    'relative flex w-full shrink-0 basis-full flex-col space-y-4',
                                    'items-center justify-center text-center' => $align === 'center',
                                    'items-start justify-start text-left' => $align === 'left',
                                    'items-end justify-end text-right' => $align === 'right',
                                ])
                            >
                                @if ($media)
                                    <x-capell::media
                                        :media="$media"
                                        :alt="$widgetAsset->asset->translation->label"
                                        rounded="full"
                                        class="h-20 w-20 object-cover"
                                        itemprop="image"
                                    />
                                @endif

                                @if ($content)
                                    <blockquote
                                        class="lg:text-md max-w-2xl italic text-white"
                                        itemprop="reviewBody"
                                    >
                                        {!! $content !!}
                                    </blockquote>
                                @endif

                                @if ($title)
                                    <div>
                                        <div
                                            class="text-sm font-bold text-white lg:text-base"
                                            itemprop="author"
                                            itemscope
                                            itemtype="https://schema.org/Person"
                                        >
                                            <span itemprop="name">
                                                {{ $title }}
                                            </span>
                                        </div>

                                        @if ($position || $company)
                                            <div
                                                class="text-smaller block font-normal text-gray-300"
                                            >
                                                <span itemprop="jobTitle">
                                                    {{ $position }}
                                                </span>
                                                @if ($company)
                                                    @if ($position)
                                                        <span class="mx-1">
                                                            |
                                                        </span>
                                                    @endif

                                                    <span itemprop="worksFor">
                                                        {{ $company }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @if ($total > 1)
                <div
                    data-carousel-controls="{{ $carouselId }}"
                    class="swiper-controls space-y-4"
                >
                    @if ($carouselArrows)
                        <div class="flex items-center justify-center gap-3">
                            <button
                                aria-label="{{ __('capell-frontend::generic.previous') }}"
                                class="swiper-button-prev pointer-events-auto relative inset-auto m-0 flex h-10 w-10 items-center justify-center rounded-full bg-white/80 text-gray-900 shadow-md transition hover:bg-white"
                                type="button"
                            ></button>
                            <button
                                aria-label="{{ __('capell-frontend::generic.next') }}"
                                class="swiper-button-next pointer-events-auto relative inset-auto m-0 flex h-10 w-10 items-center justify-center rounded-full bg-white/80 text-gray-900 shadow-md transition hover:bg-white"
                                type="button"
                            ></button>
                        </div>
                    @endif

                    <div
                        class="swiper-pagination flex justify-center"
                        wire:ignore
                    ></div>
                </div>
            @endif
        </div>
    @endif
</x-capell-layout::widget.wrapper>

<?php
