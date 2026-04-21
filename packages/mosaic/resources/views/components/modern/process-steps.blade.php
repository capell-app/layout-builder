<?php

declare(strict_types=1);

?>

@props([
    'layout' => $widget->getMeta('layout', 'horizontal'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-mosaic::widget.wrapper
    class="widget-ap-process-steps"
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
                    <h2
                        class="mb-3 text-3xl font-bold tracking-tight text-gray-900 md:text-4xl"
                    >
                        {{ $widget->translation->title }}
                    </h2>
                @endif

                @if ($widget->translation->content)
                    <p class="text-lg text-gray-500">
                        {{ strip_tags($widget->translation->content) }}
                    </p>
                @endif
            </div>
        @endif

        @if ($layout === 'horizontal')
            <div class="relative mx-auto max-w-5xl">
                <div
                    class="absolute left-0 right-0 top-12 hidden h-px bg-stone-200 md:block"
                ></div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                    @forelse ($widget->assets as $widgetAsset)
                        <div class="relative text-center">
                            <div class="relative z-10 mx-auto mb-4 h-24 w-24">
                                <div
                                    class="flex h-24 w-24 items-center justify-center rounded-full border-2 border-stone-200 bg-white text-4xl shadow-sm"
                                >
                                    {{ $widgetAsset->asset->getMeta('icon', $loop->index + 1) }}
                                </div>
                                <div
                                    class="absolute -right-1 -top-1 flex h-7 w-7 items-center justify-center rounded-full bg-stone-800 text-xs font-bold text-white"
                                >
                                    {{ $loop->index + 1 }}
                                </div>
                            </div>

                            @if ($widgetAsset->asset->translation?->title)
                                <h3
                                    class="mb-1 text-base font-bold text-gray-900"
                                >
                                    {{ $widgetAsset->asset->translation->title }}
                                </h3>
                            @endif

                            @if ($widgetAsset->asset->translation?->content)
                                <p class="text-sm text-gray-500">
                                    {{ strip_tags($widgetAsset->asset->translation->content) }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center">
                            <p class="text-gray-500">No steps configured.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            <div class="mx-auto max-w-3xl space-y-8">
                @forelse ($widget->assets as $widgetAsset)
                    <div class="flex gap-6">
                        <div
                            class="relative flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full border-2 border-stone-200 bg-white text-2xl shadow-sm"
                        >
                            {{ $widgetAsset->asset->getMeta('icon', $loop->index + 1) }}
                            <div
                                class="absolute -right-1 -top-1 flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white"
                            >
                                {{ $loop->index + 1 }}
                            </div>
                        </div>

                        <div class="flex-grow pt-2">
                            @if ($widgetAsset->asset->translation?->title)
                                <h3
                                    class="mb-1 text-lg font-bold text-gray-900"
                                >
                                    {{ $widgetAsset->asset->translation->title }}
                                </h3>
                            @endif

                            @if ($widgetAsset->asset->translation?->content)
                                <p class="text-gray-500">
                                    {{ strip_tags($widgetAsset->asset->translation->content) }}
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <p class="text-gray-500">No steps configured.</p>
                    </div>
                @endforelse
            </div>
        @endif
    </section>
</x-capell-mosaic::widget.wrapper>

<?php
