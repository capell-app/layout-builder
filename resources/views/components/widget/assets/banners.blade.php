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
>
    <div class="embla relative grid h-full w-full">
        <div class="embla__viewport h-full w-full overflow-hidden">
            <div
                class="embla__container flex h-full w-full touch-pan-y touch-pinch-zoom"
            >
                @foreach ($widget->assets as $widgetAsset)
                    @php
                        $backgroundImage = $widgetAsset->asset instanceof Media ? $widgetAsset->asset : $widgetAsset->asset->image;
                        if (! $backgroundImage) {
                            $backgroundImage = $widget->backgroundImage;
                        }
                        $title = '';
                        $content = '';
                        if (CapellCore::getAsset($widgetAsset->asset_type)->hasTranslation()) {
                            $title = $widgetAsset->asset->translation?->title;
                            $content = $widgetAsset->asset->translation?->content;
                        }

                        $linkedPageUrl = $widgetAsset->asset->linkedPage ? $widgetAsset->asset->linkedPage->pageUrl?->full_url : '';
                    @endphp

                    <div
                        class="embla__slide relative flex min-h-[20rem] w-full shrink-0 basis-full items-center justify-center"
                    >
                        @if ($backgroundImage)
                            <x-dynamic-component
                                format="webp"
                                component="capell::media.background.glider"
                                curation="hero"
                                :media="$backgroundImage"
                                :srcset="['1680w', '1024w', '640w']"
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
        </div>
        @if ($total > 1)
            <div class="embla__controls">
                <div
                    class="embla__dots absolute bottom-8 left-0 right-0 z-30 flex justify-center gap-x-3"
                ></div>
            </div>
        @endif
    </div>
</section>

<?php
