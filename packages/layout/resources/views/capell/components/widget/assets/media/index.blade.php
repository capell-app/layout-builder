<?php

declare(strict_types=1);

?>

@php
    use Capell\Core\Models\Media;
    use Capell\Frontend\Facades\Frontend;
    use Illuminate\Support\Facades\DB;
@endphp

@props([
    'colorScheme' => $widget->meta['color_scheme'] ?? 'dark',
    'columns' => $container['meta']['override_columns'] ?? ($widget->meta['columns'] ?? 4),
    'container',
    'containerKey',
    'large' => false,
    'loop',
    'size' => $widget->meta['size'] ?? '',
    'theme' => Frontend::getTheme(),
    'widget',
    'widget_theme' => $widget->meta['widget_theme'] ?? '',
])
<x-capell::widget.wrapper
    class="widget-media-gallery"
    :$container
    :$containerKey
    :index="$loop->index"
    :$widget
>
    @if ($widget->translation)
        <x-capell::content
            class="mb-5"
            :compact="true"
            :$containerKey
            :content="$widget->translation->content"
            :contents="$widget->translation->content ? null : $widget->translation->contents"
            :color-scheme="$colorScheme"
            :title="$widget->translation->title"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
        />
    @endif

    @if ($widget->assets->isNotEmpty())
        <div
            @class([
                'grid w-full gap-2 md:grid-cols-2',
                'lg:grid-cols-3' => $columns > 2 && count($widget->assets) >= 3,
                'xl:gap-6' => $size !== 'sm',
            ])
        >
            @foreach ($widget->assets as $media)
                @if ($widget_theme === 'masonry' && $widget->assets->count() > 4)
                    @if ($loop->iteration === 1 ||
                         $loop->iteration === 3 ||
                         $loop->iteration === 4 ||
                         ($loop->iteration > 8 &&
                         (($loop->iteration - 1) % 8 === 0 || ($loop->iteration - 3) % 8 === 0 || ($loop->iteration - 4) % 8 === 0)))
                        {{-- format-ignore-start --}}
                        <div
                                @class([
                                   'grid gap-2',
                                   ' md:col-span-2 md:grid-cols-2 lg:grid-cols-1 lg:col-span-1' => $loop->iteration === 4,
                                   'xl:gap-6' => $size !== 'sm'
                               ])
                        >
                            {{-- format-ignore-end --}}
                    @endif

                    @php
                        $large = ($loop->iteration - 3) % 8 === 0;
                    @endphp
                @endif

                <div
                    class="widget-media-item group relative cursor-pointer bg-gray-100 text-center"
                >
                    @if (($media->meta['media_type'] ?? null) === 'video')
                        <x-capell::media
                            :class="'h-full w-full bg-gray-50 shadow group-hover:scale-105'.($theme->withDarkMode ? ' dark:bg-gray-800' : '')"
                            :height="$large ? 600 : 300"
                            :$loop
                            :media="$media->asset"
                            :preview="(int) $media->meta['image_id']"
                            :width="440"
                            media_type="video"
                            fit="crop-center"
                            lightbox="true"
                        />
                    @else
                        <x-capell::media
                            :class="'h-full w-full bg-gray-50 shadow'.($theme->withDarkMode ? ' dark:bg-gray-800' : '')"
                            :height="$large ? 600 : 300"
                            :$loop
                            :media="$media->asset"
                            :width="440"
                            fit="crop-center"
                            lightbox="true"
                        />
                    @endif

                    @if ($media->asset->name)
                        <div
                            @class([
                                'pointer-events-none absolute inset-x-0 bottom-0 items-center justify-center
                            break-words bg-gray-600/75 px-2 py-4 font-medium leading-none leading-tight text-white',
                                'text-sm' => $size === 'sm',
                                'text-lg' => $size === 'lg',
                                'rounded-b' => $theme->meta['rounded'] ?? false,
                            ])
                        >
                            {{ $media->asset->title }}
                        </div>
                    @endif
                </div>

                @if ($widget_theme === 'masonry' && $widget->assets->count() > 4)
                    @if ($loop->iteration === 2 ||
                         $loop->iteration === 3 ||
                         $loop->iteration === 5 ||
                         ($loop->iteration > 8 &&
                         (($loop->iteration - 2) % 8 === 0 || ($loop->iteration - 3) % 8 === 0 || ($loop->iteration - 5) % 8 === 0)))
                        {{-- format-ignore-start --}}
                        </div>{{-- format-ignore-end --}}
                    @endif
                @endif
            @endforeach
        </div>
    @endif
</x-capell::widget.wrapper>

<?php
