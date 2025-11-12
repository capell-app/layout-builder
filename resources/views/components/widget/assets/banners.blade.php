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
    'containerKey',
    'containerIndex',
    'backgroundOverlay' => $widget->meta['background_overlay'] ?? false,
    'loop',
    'total' => $widget->assets->isNotEmpty() ? $widget->assets->count() : 1,
    'widget',
    'widgetIndex',
])

<section
    class="widget-banner relative flex w-full items-center justify-center overflow-hidden"
    style="
        --swiper-pagination-bottom: 2rem;
        --swiper-pagination-bullet-inactive-color: #fff;
    "
>
    <div class="swiper relative grid h-full w-full">
        <div class="swiper-wrapper h-full w-full">
            @foreach ($widget->assets as $widgetAsset)
                @php
                    $backgroundImage = $widgetAsset->asset instanceof Media ? $widgetAsset->asset : $widgetAsset->asset->image;
                    if (! $backgroundImage) {
                        $backgroundImage = $widget->backgroundImage;
                    }
                    $title = '';
                    $content = '';
                    if (CapellCore::getAsset($widgetAsset->asset_type)->hasTranslations) {
                        $title = $widgetAsset->asset->translation?->title;
                        $content = $widgetAsset->asset->translation?->content;
                    }

                    $linkedPageUrl = $widgetAsset->asset->linkedPage ? $widgetAsset->asset->linkedPage->pageUrl?->full_url : '';
                @endphp

                <div
                    @class([
                        'swiper-slide relative flex min-h-[20rem] w-full shrink-0 basis-full items-center justify-center',
                        'swiper-slide-active' => $loop->first,
                    ])
                >
                    @if ($backgroundImage)
                        <x-capell::media
                            format="webp"
                            curation="hero"
                            :media="$backgroundImage"
                            height="100vh"
                            :loading="$loop->first ? 'eager' : 'lazy'"
                            :class="
                                Illuminate\Support\Arr::toCssClasses([
                                    'absolute inset-0 w-full h-full object-cover pointer-events-none z-0 bg-no-repeat bg-center bg-cover',
                                ])
                            "
                        />
                        @if ($backgroundOverlay)
                            <div
                                class="absolute inset-0 z-10 bg-black/40 shadow-[inset_0_0_8rem_4rem_rgba(0,0,0,0.7)]"
                            ></div>
                        @endif
                    @endif

                    @if ($title || $content)
                        <div
                            class="relative z-20 flex flex-col items-center justify-center space-y-6 px-4 py-20 text-center"
                        >
                            @if ($title)
                                <h4
                                    class="font-heading text-2xl font-bold text-white md:text-4xl"
                                >
                                    @if ($linkedPageUrl)
                                        <a
                                            href="{{ $linkedPageUrl }}"
                                            class="hover:underline"
                                        >
                                            {{ $title }}
                                        </a>
                                    @else
                                        {{ $title }}
                                    @endif
                                </h4>
                            @endif

                            @if ($content)
                                <div
                                    class="max-w-2xl text-lg text-white md:text-2xl"
                                >
                                    {!! $content !!}
                                </div>
                            @endif

                            @if ($widgetAsset->asset->linkedPage?->translation)
                                <x-capell::button
                                    :url="$linkedPageUrl"
                                    color="primary"
                                    icon="heroicon-o-chevron-right"
                                >
                                    {{ $widgetAsset->asset->linkedPage->translation->link_text }}
                                </x-capell::button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
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
</section>

<?php
