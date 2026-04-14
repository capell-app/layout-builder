<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$page = Frontend::page();
$urlParams = Frontend::params();
$theme = Frontend::theme();
?>

@props([
    'backgroundColor' => $widget->getMeta('background_color'),
    'containerKey',
    'containerIndex',
    'color' => $widget->getMeta('color', $theme->getMeta('color')),
    'carouselAlign' => $widget->getMeta('carousel_align', 'center'),
    'carouselArrows' => (bool) $widget->getMeta('carousel_arrows', true),
    'carouselAutoPlay' => (bool) $widget->getMeta('carousel_auto_play', true),
    'carouselAutoDelay' => (int) $widget->getMeta('carousel_auto_delay', 8000),
    'carouselButtonClass' => 'hover:bg-primary focus:bg-primary pointer-events-auto bg-white/80 shadow-md transition hover:text-white focus:text-white disabled:pointer-events-none disabled:opacity-50',
    'carouselDisableOnInteraction' => (bool) $widget->getMeta('carousel_disable_on_interaction', true),
    'carouselDrag' => (bool) $widget->getMeta('carousel_drag', true),
    'carouselEffect' => $widget->getMeta('carousel_effect', 'slide'),
    'carouselFade' => (bool) $widget->getMeta('carousel_fade', false),
    'carouselLoop' => (bool) $widget->getMeta('carousel_loop', true),
    'carouselPagination' => (bool) $widget->getMeta('carousel_pagination', true),
    'carouselPauseOnHover' => (bool) $widget->getMeta('carousel_pause_on_hover', true),
    'carouselRewind' => (bool) $widget->getMeta('carousel_rewind', false),
    'carouselSpeed' => (int) $widget->getMeta('carousel_speed', 300),
    'carouselTouch' => $widget->getMeta('carousel_touch'),
    'carouselWheel' => (bool) $widget->getMeta('carousel_wheel', true),
    'heroContent' => null,
    'loop',
    'total' => $widget->assets->count(),
    'slideClass' => '',
    'widget',
    'widgetIndex',
])
{{-- format-ignore-start --}}
@php
    use Capell\Layout\Actions\GetWidgetContainerWidthAction;

    if ($containerIndex === 0 && $theme->getMeta('header_position') === 'fixed') {
        $slideClass .= ' pt-20 lg:pt-32';
    }

    $height = $widget->getMeta('height', '36em');

    $containerClass = GetWidgetContainerWidthAction::run($widget);
@endphp
{{-- format-ignore-end --}}
<section
    @class([
        'widget-hero relative z-10 grid w-full',
        'mb-10' => ! $loop->last,
        'mt-10' => ! $loop->first,
        'bg-gray-50 dark:bg-gray-900' => $color === 'light',
        'bg-gray-800 dark:bg-gray-900' => $color === 'dark',
        'min-h-[calc(100vh-var(--header-height))]' => $height === 'full',
        'h-[calc(100vh-var(--header-height))] max-h-[var(--hero-height)]' => filled($height) && $height !== 'full',
    ])
    style="--hero-height: {{ $height }}"
>
    <x-capell-hero::hero.wrapper
        :key="$containerKey . '-widget-' . $widgetIndex"
        :total="$total"
        :carousel-align="$carouselAlign"
        :carousel-arrows="$carouselArrows"
        :carousel-auto-play="$carouselAutoPlay"
        :carousel-auto-delay="$carouselAutoDelay"
        :carousel-button-class="$carouselButtonClass"
        :carousel-disable-on-interaction="$carouselDisableOnInteraction"
        :carousel-drag="$carouselDrag"
        :carousel-effect="$carouselEffect"
        :carousel-fade="$carouselFade"
        :carousel-loop="$carouselLoop"
        :carousel-pagination="$carouselPagination"
        :carousel-pause-on-hover="$carouselPauseOnHover"
        :carousel-rewind="$carouselRewind"
        :carousel-speed="$carouselSpeed"
        :carousel-touch="$carouselTouch"
        :carousel-wheel="$carouselWheel"
    >
        @if ($widget->assets->isNotEmpty())
            @foreach ($widget->assets as $widgetAsset)
                {{-- format-ignore-start --}}
                @php
                    /** @var \Capell\Layout\Models\WidgetAsset $widgetAsset */
                    $slideColorScheme = $widgetAsset->asset->getMeta('color', $color);

                    $linkedPage = $widgetAsset->asset instanceof \Capell\Core\Models\Page ? $widgetAsset->asset : $widgetAsset->asset->linkedPage;

                    $url = null;
                    if ($linkedPage) {
                        $url = $linkedPage->pageUrl->full_url;
                    }

                    if ($widgetAsset->asset instanceof \Capell\Core\Models\Media) {
                        $bgImage = $widgetAsset->asset;
                        $images = null;
                    } else {
                        $bgCollectionName = \Capell\Core\Enums\MediaCollectionEnum::BackgroundImage->value;
                        $bgImage = $widgetAsset->media?->firstWhere('collection_name', $bgCollectionName)
                            ?? $widgetAsset->asset->media?->firstWhere('collection_name', $bgCollectionName);

                        $imageCollectionName = \Capell\Core\Enums\MediaCollectionEnum::Image->value;
                        $images = $widgetAsset->media?->where('collection_name', $imageCollectionName);
                        if (! $images?->isNotEmpty()) {
                            $images = $widgetAsset->asset->media?->where('collection_name', $imageCollectionName);
                        }
                    }
                @endphp
                {{-- format-ignore-end --}}
                <x-capell-hero::hero.slide
                    :background-image="$bgImage"
                    :background-color="$widgetAsset->asset->getMeta('background_color', $backgroundColor)"
                    :background-size="$widgetAsset->asset->getMeta('background_size', $widget->getMeta('background_size', 'cover'))"
                    :background-position="$widgetAsset->asset->getMeta('background_position', $widget->getMeta('background_position', 'center'))"
                    :background-attachment="$widgetAsset->asset->getMeta('background_attachment', $widget->getMeta('background_attachment', 'scroll'))"
                    :background-repeat="$widgetAsset->asset->getMeta('background_repeat', $widget->getMeta('background_repeat', 'no-repeat'))"
                    :background-overlay="$bgImage && $widgetAsset->asset->translation ? $color : ''"
                    :first="$loop->first"
                    :total="$total"
                    :title="$widgetAsset->asset->translation->title"
                    :color="$slideColorScheme"
                    :container-class="$containerClass->getContainerClass()"
                    :class="$slideClass"
                >
                    <div
                        @class([
                            '@container grid select-text gap-4 gap-x-10 gap-y-8 py-14 lg:gap-x-16 lg:py-24',
                            'lg:grid-cols-12' => $images?->isNotEmpty(),
                        ])
                    >
                        <div
                            @class([
                                'flex flex-col justify-center',
                                'items-center text-center' => ! $images?->isNotEmpty(),
                                'lg:col-span-5 xl:col-span-7' => $images?->isNotEmpty(),
                                'py-[4vh]' => ! $widgetAsset->asset->image && ! $bgImage,
                            ])
                        >
                            @if ($widgetAsset->asset)
                                <x-capell-hero::hero.content
                                    :title="$widgetAsset->asset->translation->title"
                                    :heading-size="$loop->first ? 'h1' : 'h2'"
                                    :$url
                                    :color="$slideColorScheme"
                                    :size="! $images?->isNotEmpty() ? 'lg' : 'md'"
                                >
                                    {!! $widgetAsset->asset->translation->content !!}

                                    @if ($widgetAsset->asset->getMeta('link_text'))
                                        <a
                                            class="text-link hover:text-primary font-medium no-underline focus:underline"
                                            href="{{ $url }}"
                                            wire:navigate
                                        >
                                            @svg('heroicon-s-chevron-right', 'mr-2 inline-block h-6 w-6')
                                            {{ $widgetAsset->asset->getMeta('link_text') }}
                                        </a>
                                    @endif

                                    @if ($loop->first && $heroContent)
                                        {{ $heroContent }}
                                    @endif
                                </x-capell-hero::hero.content>
                            @endif

                            @if ($widgetAsset->asset->related?->isNotEmpty())
                                <x-capell-hero::hero.related
                                    class="w-full"
                                    :related="$widgetAsset->asset->related"
                                    :key="$containerKey . '-widget-' . $widgetIndex . '-related'"
                                />
                            @endif

                            @if ($widgetAsset->asset->getMeta('actions'))
                                <x-capell-layout::actions
                                    class="hero-actions mt-8 w-full"
                                    :actions="$widgetAsset->asset->getMeta('actions')"
                                    :color="$slideColorScheme"
                                    action-item-class="hero-action-item"
                                />
                            @endif
                        </div>

                        @if ($images?->isNotEmpty())
                            <div
                                class="relative z-30 flex w-full items-center lg:col-span-6 xl:col-span-5"
                            >
                                @foreach ($images as $media)
                                    @capellBuffer($mediaContent)
                                        <x-capell::media
                                            format="webp"
                                            :media="$media"
                                            :alt="$widgetAsset->asset->translation->title"
                                            class="hero-slide-img h-full max-h-[40vh] w-full object-cover object-center lg:max-h-[400px]"
                                            :loading="$loop->first ? 'eager' : 'lazy'"
                                        />
                                    @endcapellBuffer

                                    @if ($loop->first)
                                        {{ $mediaContent() }}
                                        @continue
                                    @endif

                                    <div
                                        class="z-12 absolute -bottom-4 left-4 w-2/3 rounded-lg bg-gray-200 shadow-lg lg:-left-8 dark:bg-gray-800"
                                    >
                                        {{ $mediaContent() }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-capell-hero::hero.slide>
            @endforeach
        @elseif ($page->translation->getMeta('hero'))
            <x-capell-hero::hero.slide
                :background-image="$widget->image"
                :background-color="$widget->getMeta('background_color', $theme->getMeta('background_color'))"
                :background-size="$widget->getMeta('background_size', 'cover')"
                :background-position="$widget->getMeta('background_position', 'center')"
                :background-attachment="$widget->getMeta('background_attachment', 'scroll')"
                :background-repeat="$widget->getMeta('background_repeat', 'no-repeat')"
                :first="true"
                :total="1"
                :color="$color"
                container-class="container"
            >
                <div class="@lg:py-16 flex select-text py-20">
                    <x-capell-hero::hero.content
                        :title="
                            $widget->translation
                            ? __($widget->translation->title, $urlParams)
                            : null
                        "
                        :color="$color"
                        size="lg"
                        class="hero-page-content"
                    >
                        {!! __($page->translation->getMeta('hero'), $urlParams) !!}
                    </x-capell-hero::hero.content>
                </div>
            </x-capell-hero::hero.slide>
        @endif
    </x-capell-hero::hero.wrapper>
</section>

<?php
