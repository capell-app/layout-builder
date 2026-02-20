<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    $theme = Frontend::theme();
@endphp

@props([
    'color' => $widget->meta['color'] ?? 'dark',
    'columns' => $container['meta']['override_columns'] ?? ($widget->meta['columns'] ?? 4),
    'container',
    'containerKey',
    'containerWidth' => null,
    'large' => false,
    'loop',
    'pageContainer' => $widget->meta['container'] ?? $theme->meta['container'] ?? null,
    'size' => $widget->meta['size'] ?? '',
    'spacing' => $widget->meta['spacing'] ?? null,
    'widget',
    'widget_theme' => $widget->meta['widget_theme'] ?? '',
])
<x-capell-layout::widget.wrapper
    :class="'widget-media-gallery' . ($pageContainer === 'full' ? ' px-4' : '')"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    @if ($widget->translation)
        <x-capell::content
            :class="'mb-5' . ($pageContainer === 'full' ? ' container' : '')"
            :compact="true"
            align="center"
            :content="$widget->translation->content"
            :content-type="$widget->type->content_structure"
            :color="$color"
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :title="$widget->translation->title"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? 'center'"
            :heading-style="($widget->meta['heading_style'] ?? null) ?: $widget->type->meta['heading_style'] ?? null"
        />
    @endif

    @if ($widget->assets->isNotEmpty())
        <div
            @class([
                'grid grid-cols-2 2xl:container md:grid-cols-3',
                'gap-2' => $spacing === 'sm',
                'gap-4' => $spacing === 'md',
                'gap-6' => $spacing === 'lg',
            ])
        >
            @foreach ($widget->assets as $widgetAsset)
                {{-- format-ignore-start --}}
                @php
                    $image = $widgetAsset->media->first() ?: $widgetAsset->asset->image;
                    if (! $image) {
                        report('Image not found for WidgetAsset: ' . $widgetAsset->asset_type . ' ' . $widgetAsset->id);
                        continue;
                    }
                @endphp
                {{-- format-ignore-end --}}
                <div
                    @class([
                        'widget-media-item group relative h-full cursor-pointer overflow-hidden text-center',
                        'md:col-span-1 md:row-span-2' => ($loop->iteration > 5 && $loop->iteration % 5 === 0) || $loop->iteration === 2,
                    ])
                    tabindex="0"
                >
                    @if (Str::startsWith($image->mime_type, 'video/'))
                        <x-capell::media
                            :class="'h-full w-full bg-gray-50 object-cover shadow transition-transform duration-300 group-hover:scale-105 group-focus-within:scale-105' . ($theme->withDarkMode ? ' dark:bg-gray-800' : '')"
                            :height="$large ? 600 : 300"
                            :$loop
                            :media="$image"
                            :preview="(int) $image->meta['image_id']"
                            :alt="$widgetAsset->asset->translation?->label"
                            :width="440"
                            media_type="video"
                            fit="crop-center"
                            lightbox="true"
                        />
                    @else
                        <x-capell::media
                            :class="'h-full w-full bg-gray-50 object-cover shadow transition-transform duration-300 group-hover:scale-105 group-focus-within:scale-105' . ($theme->withDarkMode ? ' dark:bg-gray-800' : '')"
                            :height="$large ? 600 : 300"
                            :$loop
                            :media="$image"
                            :alt="$widgetAsset->asset->translation?->label"
                            :width="440"
                            fit="crop-center"
                            lightbox="true"
                        />
                    @endif

                    @if ($widgetAsset->asset->translation?->title)
                        <div
                            @class([
                                'pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-center
                            break-words bg-gray-600/75 px-2 py-4 font-medium leading-none leading-tight text-white
                            transform translate-y-full opacity-0 transition-all duration-300
                            group-hover:translate-y-0 group-hover:opacity-100
                            group-focus-within:translate-y-0 group-focus-within:opacity-100',
                                'text-sm' => $size === 'sm',
                                'text-lg' => $size === 'lg',
                                'rounded-b' => $theme->meta['rounded_images'] ?? false,
                            ])
                        >
                            {{ $widgetAsset->asset->translation->title }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-capell-layout::widget.wrapper>

<?php
