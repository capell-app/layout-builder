<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Facades\CapellCore;
    use Capell\Frontend\Facades\FrontendLoader;
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    $page = FrontendLoader::getPage();
    $theme = FrontendLoader::getTheme();
@endphp

@props([
    'align' => $widget->meta['align'] ?? $widget->type->meta['align'] ?? 'center',
    'colorScheme' => $widget->meta['color_scheme'] ?? $widget->type->meta['color_scheme'] ?? 'light',
    'containerKey',
    'containerIndex',
    'containerWidth',
    'loop',
    'total' => $widget->assets->isNotEmpty() ? $widget->assets->count() : 1,
    'widget',
    'widgetIndex',
])

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
            :color-scheme="$colorScheme"
            :presenter="$widget->type->meta['content_presenter'] ?? null"
            :title="$widget->translation->title"
            heading-weight="semibold"
            :text-align="$align"
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
            <div class="swiper swiper-fade grid h-full w-full">
                <div class="swiper-wrapper h-full w-full">
                    @foreach ($widget->assets as $widgetAsset)
                        @php
                            $title = '';
                            $content = '';
                            $image = $widgetAsset->asset instanceof Media ? $widgetAsset->asset : $widgetAsset->asset->image;

                            if (CapellCore::getAsset($widgetAsset->asset_type)->hasTranslations) {
                                $title = $widgetAsset->asset->translation?->title;
                                $content = $widgetAsset->asset->translation?->content;
                            }
                        @endphp

                        <div
                            class="swiper-slide"
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
                                @if ($image)
                                    <x-capell::media
                                        :media="$image"
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

                                        @if (! empty($widgetAsset->asset->translation->meta['position']) || ! empty($widgetAsset->asset->translation->meta['company']))
                                            <div
                                                class="text-smaller block font-normal text-gray-300"
                                            >
                                                <span itemprop="jobTitle">
                                                    {{ $widgetAsset->asset->translation->meta['position'] ?? '' }}
                                                </span>
                                                @if (! empty($widgetAsset->asset->translation->meta['company']))
                                                    @if (! empty($widgetAsset->asset->translation->meta['position']))
                                                        <span class="mx-1">
                                                            |
                                                        </span>
                                                    @endif

                                                    <span itemprop="worksFor">
                                                        {{ $widgetAsset->asset->translation->meta['company'] }}
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
                <div class="swiper-controls">
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
