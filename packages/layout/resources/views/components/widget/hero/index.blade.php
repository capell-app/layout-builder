<?php

declare(strict_types=1);

?>

@php
    use Awcodes\Curator\Models\Media;
    use Capell\Frontend\Actions\ReplacePageDataAction;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Services\Loader\PageLoader;
@endphp

@props([
    'backgroundColor' => $widget->meta['background_color'] ?? null,
    'backgroundImage' => ! empty($widget->meta['background_image']) ? Media::find($widget->meta['background_image']) : null,
    'containerKey',
    'containerIndex',
    'colorScheme' => $widget->meta['color_scheme'] ?? $theme->meta['color_scheme'] ?? null,
    'heroContent' => null,
    'loop',
    'pageRecord' => Frontend::getPage(),
    'pageRecordParams' => Frontend::getPageParams(),
    'total' => $widget->assets->isNotEmpty() ? $widget->assets->count() : 1,
    'theme' => Frontend::getTheme(),
    'slideClass' => '',
    'widget',
    'widgetIndex',
])

@php
    if ($containerIndex === 0 && ($theme->meta['header_position'] ?? null) === 'fixed') {
        $slideClass .= ' pt-20 lg:pt-32';
    }
    $height = $widget->meta['height'] ?? null;
@endphp

<section
    @class([
        'widget-hero relative z-10 grid w-full',
        'mb-10' => ! $loop->last,
        'mt-10' => ! $loop->first,
        'bg-gray-50 dark:bg-gray-900' => $colorScheme === 'light',
        'bg-gray-800 dark:bg-gray-900' => $colorScheme === 'dark',
        'min-h-screen' => $height === 'full',
        'lg:max-h-screen' => $height !== 'full',
        'min-h-[700px] lg:min-h-[900px]' => $height === 'large',
        'min-h-[500px] md:min-h-[600px]' => $height === 'medium',
        'min-h-[300px] sm:min-h-[400px]' => $height === 'small',
    ])
>
    <x-capell-layout::hero.wrapper
        :key="$containerKey.'-widget-'.$widgetIndex"
        :total="$total"
        :carousel-arrows="$widget->meta['carousel_arrows'] ?? false"
        :carousel-auto="$widget->meta['carousel_auto'] ?? true"
        :carousel-auto-delay="$widget->meta['carousel_auto_delay'] ?? 8000"
        :carousel-loop="$widget->meta['carousel_loop'] ?? true"
        :carousel-type="$widget->meta['carousel_type'] ?? null"
        :carousel-pagination="$widget->meta['carousel_pagination'] ?? true"
    >
        @if ($widget->assets->isEmpty())
            @php
                $content = $pageRecord->translation->meta['hero'] ?? $widget->translation->content;
            @endphp

            <x-capell-layout::hero.slide
                :background-image="$widget->image ?: $backgroundImage"
                :background-color="$widget->meta['background_color'] ?? ($theme['meta']['background_color'] ?? '')"
                :background-size="$widget->meta['background_size'] ?? 'cover'"
                :background-position="$widget->meta['background_position'] ?? 'center'"
                :background-attachment="$widget->meta['background_attachment'] ?? 'scroll'"
                :background-repeat="$widget->meta['background_repeat'] ?? 'no-repeat'"
                :carousel-type="$widget->meta['carousel_type'] ?? null"
                :first="true"
                :total="1"
                :color-scheme="$colorScheme"
                container-class="container"
            >
                <div class="@lg:py-16 flex select-text py-20">
                    <x-capell-layout::hero.content
                        :title="
                            $widget->translation
                            ? ReplacePageDataAction::run($widget->translation->title, $pageRecordParams)
                            : null
                        "
                        :color-scheme="$colorScheme"
                        size="lg"
                    >
                        {!! ReplacePageDataAction::run($content, $pageRecordParams) !!}
                    </x-capell-layout::hero.content>
                </div>
            </x-capell-layout::hero.slide>
        @else
            @foreach ($widget->assets as $widgetAsset)
                @php
                    $asset = $widgetAsset->asset;
                    if (! $asset) {
                        continue;
                    }

                    $slideColorScheme = $asset->meta['color_scheme'] ?? $colorScheme;

                    $url = null;
                    if ($widgetAsset->relatedPage) {
                        $pageUrl = PageLoader::getPageUrlById(
                            pageId: $widgetAsset->relatedPage->id,
                            site: $widgetAsset->relatedPage->site,
                            language: $language,
                        );

                        $url = $pageUrl?->full_url;
                    }

                    if ($asset instanceof Media) {
                        $bgImage = $asset;
                        $images = null;
                    } else {
                        $bgImage = ! empty($asset->meta['background_image_id'])
                            ? Media::find($asset->meta['background_image_id'])
                            : ($asset->image ?: $backgroundImage);

                        $images = $asset->media;
                    }

                    if (! $bgImage && ! $images?->isNotEmpty() && ! $asset->translation) {
                        continue;
                    }
                @endphp

                <x-capell-layout::hero.slide
                    :background-image="$bgImage"
                    :background-color="($asset->meta['background_color'] ?? null) ?: $backgroundColor"
                    :background-size="
                        ($asset->meta['background_size'] ?? null)
                        ?: ($widget->meta['background_size'] ?? 'cover')
                    "
                    :background-position="
                        ($asset->meta['background_position'] ?? null)
                        ?: ($widget->meta['background_position'] ?? 'center')
                    "
                    :background-attachment="
                        ($asset->meta['background_attachment'] ?? null)
                        ?: ($widget->meta['background_attachment'] ?? 'scroll')
                    "
                    :background-repeat="
                        ($asset->meta['background_repeat'] ?? null)
                        ?: ($widget->meta['background_repeat'] ?? 'no-repeat')
                    "
                    :background-overlay="$bgImage && $asset->translation ? $colorScheme : ''"
                    :first="$loop->first"
                    :total="$total"
                    :color-scheme="$slideColorScheme"
                    :class="$slideClass"
                    container-class="container"
                >
                    <div
                        @class([
                            '@container pb-22 grid select-text gap-4 gap-x-10 gap-y-8 pt-8 lg:min-h-full lg:gap-x-16 lg:pt-16',
                            'lg:max-h-screen' => $height !== 'full',
                            'lg:grid-cols-12' => $images?->isNotEmpty(),
                        ])
                    >
                        <div
                            @class([
                                'flex min-h-[20vh] flex-col justify-center',
                                'items-center text-center' => ! $images?->isNotEmpty(),
                                'lg:col-span-8 xl:col-span-7' => $images?->isNotEmpty(),
                                'py-[4vh]' => ! $asset->image && ! $bgImage,
                            ])
                        >
                            @if ($asset->translation)
                                <x-capell-layout::hero.content
                                    :title="$asset->translation->title"
                                    :heading-size="$loop->first ? 'h1' : 'h2'"
                                    :$url
                                    :color-scheme="$slideColorScheme"
                                    :size="! $images?->isNotEmpty() ? 'lg' : 'md'"
                                >
                                    {!! $asset->translation->content !!}

                                    @if (! empty($asset->meta['link_text']))
                                        <a
                                            class="text-link hover:text-primary font-medium no-underline focus:underline"
                                            href="{{ $url }}"
                                            wire:navigate
                                        >
                                            @svg('heroicon-s-chevron-right', 'mr-2 inline-block h-6 w-6')
                                            {{ $asset->meta['link_text'] }}
                                        </a>
                                    @endif

                                    @if ($loop->first && $heroContent)
                                        {{ $heroContent }}
                                    @endif
                                </x-capell-layout::hero.content>
                            @endif

                            @if ($asset->related?->isNotEmpty())
                                <x-capell-layout::hero.related
                                    class="w-full"
                                    :features="$asset->related"
                                    :key="$containerKey.'-widget-'.$widgetIndex.'-features'"
                                />
                            @endif

                            @if ($asset->meta['actions'] ?? null)
                                <x-capell::actions
                                    class="mt-8 w-full"
                                    :actions="$asset->meta['actions']"
                                    :color-scheme="$slideColorScheme"
                                />
                            @endif
                        </div>

                        @if ($images?->isNotEmpty())
                            <div
                                class="relative z-30 w-full lg:col-span-4 xl:col-span-5"
                            >
                                @foreach ($images as $media)
                                    @capture($mediaContent)
                                        <x-dynamic-component
                                            format="webp"
                                            :component="$media->hasCuration('thumbnail') ? 'curator-curation' : 'curator-glider'"
                                            curation="thumbnail"
                                            :media="$media"
                                            class="h-full max-h-[400px] w-full object-cover object-center"
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
                </x-capell-layout::hero.slide>
            @endforeach
        @endif
    </x-capell-layout::hero.wrapper>
</section>

<?php
