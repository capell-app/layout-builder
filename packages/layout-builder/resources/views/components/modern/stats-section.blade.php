@props([
    'layout' => $widget->getMeta('layout', 'horizontal'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-layout-builder::widget.wrapper
    class="widget-ap-stats-section"
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

        <div
            @class([
                'mx-auto grid gap-6',
                'max-w-md grid-cols-1' => $layout === 'vertical',
                'max-w-5xl grid-cols-2 md:grid-cols-4' => $layout !== 'vertical',
            ])
        >
            @forelse ($widget->assets as $widgetAsset)
                <div
                    class="rounded-xl border border-stone-200 bg-white p-8 text-center"
                >
                    @if ($widgetAsset->asset->getMeta('icon'))
                        <div class="mb-3 text-4xl">
                            {{ $widgetAsset->asset->getMeta('icon') }}
                        </div>
                    @endif

                    @if ($widgetAsset->asset->translation?->content)
                        <p
                            class="mb-1 text-3xl font-bold text-emerald-700 md:text-4xl"
                        >
                            {{ strip_tags($widgetAsset->asset->translation->content) }}
                        </p>
                    @endif

                    @if ($widgetAsset->asset->translation?->title)
                        <p class="text-sm font-medium text-gray-500">
                            {{ $widgetAsset->asset->translation->title }}
                        </p>
                    @endif
                </div>
            @empty
                <div class="col-span-full py-12 text-center">
                    <p class="text-gray-500">No stats configured.</p>
                </div>
            @endforelse
        </div>
    </section>
</x-capell-layout-builder::widget.wrapper>
