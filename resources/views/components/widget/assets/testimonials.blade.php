<?php

declare(strict_types=1);

?>

@php
    use Awcodes\Curator\Models\Media;
    use Capell\Core\Facades\CapellCore;
    use Capell\Frontend\Facades\Frontend;

    $page = Frontend::getPage();
    $theme = Frontend::getTheme();
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
            :contents="$widget->translation->content ? null : $widget->translation->contents"
            :color-scheme="$colorScheme"
            :title="$widget->translation->title"
            heading-weight="semibold"
            :text-align="$align"
        />
    @endif

    @if ($widget->assets->isNotEmpty())
        <div class="embla embla__fade relative grid h-full w-full">
            <div class="embla__viewport h-full w-full overflow-hidden">
                <div
                    class="embla__container flex h-full w-full touch-pan-y touch-pinch-zoom"
                >
                    @foreach ($widget->assets as $widgetAsset)
                        @php
                            $title = '';
                            $content = '';
                            $image = $widgetAsset->asset instanceof Media ? $widgetAsset->asset : $widgetAsset->asset->image;

                            if (CapellCore::getAsset($widgetAsset->asset_type)->hasTranslation()) {
                                $title = $widgetAsset->asset->translation?->title;
                                $content = $widgetAsset->asset->translation?->content;
                            }
                        @endphp

                        <div
                            @class([
                                'embla__slide relative flex w-full shrink-0 basis-full flex-col space-y-4',
                                'items-center justify-center text-center' => $align === 'center',
                                'items-start justify-start text-left' => $align === 'left',
                                'items-end justify-end text-right' => $align === 'right',
                            ])
                        >
                            @if ($image)
                                <x-capell::media
                                    :media="$image"
                                    rounded="full"
                                    class="h-20 w-20"
                                />
                            @endif

                            @if ($content)
                                <div class="lg:text-md max-w-2xl text-white">
                                    {!! $content !!}
                                </div>
                            @endif

                            @if ($title)
                                <div>
                                    <div
                                        class="text-sm font-bold text-white lg:text-base"
                                    >
                                        {{ $title }}
                                    </div>

                                    @if (! empty($widgetAsset->asset->translation->meta['position']) || ! empty($widgetAsset->asset->translation->meta['company']))
                                        <div
                                            class="text-smaller block font-normal text-gray-300"
                                        >
                                            {{ $widgetAsset->asset->translation->meta['position'] ?? '' }}
                                            @if (! empty($widgetAsset->asset->translation->meta['company']))
                                                @if (! empty($widgetAsset->asset->translation->meta['position']))
                                                    <span class="mx-1">|</span>
                                                @endif

                                                {{ $widgetAsset->asset->translation->meta['company'] }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @if ($total > 1)
                <div class="embla__controls mt-4">
                    <div class="embla__dots flex justify-center gap-x-3"></div>
                </div>
            @endif
        </div>
    @endif
</x-capell-layout::widget.wrapper>

<?php
