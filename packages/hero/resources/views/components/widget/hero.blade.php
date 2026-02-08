<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$page = Frontend::page();
$urlParams = Frontend::params();
$theme = Frontend::theme();
?>

@props([
    'backgroundColor' => $widget->meta['background_color'] ?? null,
    'containerKey',
    'containerIndex',
    'color' => $widget->meta['color'] ?? $theme->meta['color'] ?? null,
    'heroContent' => null,
    'loop',
    'total' => $widget->assets->isNotEmpty() ? $widget->assets->count() : 1,
    'slideClass' => '',
    'widget',
    'widgetIndex',
])
{{-- format-ignore-start --}}
@php
    if ($containerIndex === 0 && ($theme->meta['header_position'] ?? null) === 'fixed') {
        $slideClass .= ' pt-20 lg:pt-32';
    }

    $height = $widget->meta['height'] ?? null;
@endphp
{{-- format-ignore-end --}}
<section
    @class([
        'widget-hero relative z-10 grid w-full',
        'mb-10' => ! $loop->last,
        'mt-10' => ! $loop->first,
        'bg-gray-50 dark:bg-gray-900' => $color === 'light',
        'bg-gray-800 dark:bg-gray-900' => $color === 'dark',
        'h-[calc(100vh-var(--header-height))]' => $height === 'full',
        'h-[calc(100vh-var(--header-height))] lg:max-h-[60vh]' => $height === 'large',
        'h-[calc(100vh-var(--header-height))] md:max-h-[40vh]' => $height === 'medium',
        'h-[calc(100vh-var(--header-height))] sm:max-h-[24rem]' => $height === 'small',
    ])
>
    <x-capell-hero::hero.wrapper
        :key="$containerKey . '-widget-' . $widgetIndex"
        :total="$total"
        :carousel-arrows="$widget->meta['carousel_arrows'] ?? false"
        :carousel-auto="$widget->meta['carousel_auto'] ?? true"
        :carousel-auto-delay="$widget->meta['carousel_auto_delay'] ?? 8000"
        :carousel-loop="$widget->meta['carousel_loop'] ?? true"
        :carousel-type="$widget->meta['carousel_type'] ?? null"
        :carousel-pagination="$widget->meta['carousel_pagination'] ?? true"
    >
        @if ($widget->assets->isNotEmpty())
            @foreach ($widget->assets as $widgetAsset)
                {{-- format-ignore-start --}}
                @php
                    /** @var \Capell\Layout\Models\WidgetAsset $widgetAsset */
                    $slideColorScheme = $widgetAsset->asset->meta['color'] ?? $color;

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
                    :background-color="($widgetAsset->asset->meta['background_color'] ?? null) ?: $backgroundColor"
                    :background-size="
                        ($widgetAsset->asset->meta['background_size'] ?? null)
                        ?: ($widget->meta['background_size'] ?? 'cover')
                    "
                    :background-position="
                        ($widgetAsset->asset->meta['background_position'] ?? null)
                        ?: ($widget->meta['background_position'] ?? 'center')
                    "
                    :background-attachment="
                        ($widgetAsset->asset->meta['background_attachment'] ?? null)
                        ?: ($widget->meta['background_attachment'] ?? 'scroll')
                    "
                    :background-repeat="
                        ($widgetAsset->asset->meta['background_repeat'] ?? null)
                        ?: ($widget->meta['background_repeat'] ?? 'no-repeat')
                    "
                    :background-overlay="$bgImage && $widgetAsset->asset->translation ? $color : ''"
                    :first="$loop->first"
                    :total="$total"
                    :title="$widgetAsset->asset->translation->title"
                    :color="$slideColorScheme"
                    :class="$slideClass"
                    container-class="container"
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

                                    @if (! empty($widgetAsset->asset->meta['link_text']))
                                        <a
                                            class="text-link hover:text-primary font-medium no-underline focus:underline"
                                            href="{{ $url }}"
                                            wire:navigate
                                        >
                                            @svg('heroicon-s-chevron-right', 'mr-2 inline-block h-6 w-6')
                                            {{ $widgetAsset->asset->meta['link_text'] }}
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

                            @if ($widgetAsset->asset->meta['actions'] ?? null)
                                <x-capell::actions
                                    class="hero-actions mt-8 w-full"
                                    :actions="$widgetAsset->asset->meta['actions']"
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
                                    @capture($mediaContent)
                                        <x-capell::media
                                            format="webp"
                                            :media="$media"
                                            :alt="$widgetAsset->asset->translation->title"
                                            class="hero-slide-img h-full max-h-[40vh] w-full object-cover object-center lg:max-h-[400px]"
                                            loading="lazy"
                                        />
                                    @endcapture

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
        @elseif (isset($page->translation->meta['hero']))
            <x-capell-hero::hero.slide
                :background-image="$widget->image"
                :background-color="$widget->meta['background_color'] ?? ($theme['meta']['background_color'] ?? '')"
                :background-size="$widget->meta['background_size'] ?? 'cover'"
                :background-position="$widget->meta['background_position'] ?? 'center'"
                :background-attachment="$widget->meta['background_attachment'] ?? 'scroll'"
                :background-repeat="$widget->meta['background_repeat'] ?? 'no-repeat'"
                :carousel-type="$widget->meta['carousel_type'] ?? null"
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
                        {!! __($page->translation->meta['hero'], $urlParams) !!}
                    </x-capell-hero::hero.content>
                </div>
            </x-capell-hero::hero.slide>
        @endif
    </x-capell-hero::hero.wrapper>
</section>

<?php
