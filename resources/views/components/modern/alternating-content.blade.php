<?php

declare(strict_types=1);

?>

@props([
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-mosaic::widget.wrapper
    class="widget-ap-alternating-content"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section class="px-6 py-12 md:px-12 md:py-16">
        @if ($widget->translation)
            <div class="mx-auto mb-12 max-w-2xl text-center">
                @if ($widget->translation->title)
                    <h2 class="text-3xl font-bold text-gray-900 md:text-4xl">
                        {{ $widget->translation->title }}
                    </h2>
                @endif

                @if ($widget->translation->content)
                    <p class="mt-3 text-lg text-gray-500">
                        {{ strip_tags($widget->translation->content) }}
                    </p>
                @endif
            </div>
        @endif

        <div class="mx-auto max-w-5xl space-y-16">
            @forelse ($widget->assets as $widgetAsset)
                @php
                    $isRight = $widgetAsset->asset->getMeta('position') === 'right';
                @endphp

                <div class="grid grid-cols-1 items-center gap-8 md:grid-cols-2">
                    <div
                        @class([
                            'flex min-h-64 items-center justify-center rounded-2xl bg-gray-50 p-8',
                            'md:order-last' => $isRight,
                        ])
                    >
                        @if ($widgetAsset->asset->getMeta('icon'))
                            <span class="text-8xl">
                                {{ $widgetAsset->asset->getMeta('icon') }}
                            </span>
                        @elseif ($widgetAsset->asset->media->first() ?? $widgetAsset->asset->image ?? null)
                            @php
                                $media = $widgetAsset->asset->media->first() ?? $widgetAsset->asset->image;
                            @endphp

                            <img
                                src="{{ $media->getFullUrl() }}"
                                alt="{{ $widgetAsset->asset->translation?->title }}"
                                class="h-full w-full rounded-xl object-cover"
                            />
                        @endif
                    </div>

                    <div>
                        <div
                            class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white"
                        >
                            {{ $loop->index + 1 }}
                        </div>

                        @if ($widgetAsset->asset->translation?->title)
                            <h3 class="mb-3 text-2xl font-bold text-gray-900">
                                {{ $widgetAsset->asset->translation->title }}
                            </h3>
                        @endif

                        @if ($widgetAsset->asset->translation?->content)
                            <p class="text-base leading-relaxed text-gray-600">
                                {{ strip_tags($widgetAsset->asset->translation->content) }}
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-12 text-center">
                    <p class="text-gray-500">No content sections configured.</p>
                </div>
            @endforelse
        </div>
    </section>
</x-capell-mosaic::widget.wrapper>

<?php
