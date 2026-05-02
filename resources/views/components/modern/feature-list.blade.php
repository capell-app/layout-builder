@props([
    'layout' => $widget->getMeta('layout', 'grid'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-mosaic::widget.wrapper
    class="widget-ap-feature-list"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section
        style="padding: 3rem 2rem; background-color: var(--mosaic-surface)"
    >
        @if ($widget->translation)
            <div style="margin-bottom: 2.5rem; max-width: 38rem">
                @if ($widget->translation->title)
                    <h2
                        class="ap-feature-list-headline"
                        style="
                            color: var(--mosaic-on-surface);
                            font-family: var(--mosaic-font-headline);
                            font-size: var(--mosaic-text-headline-lg);
                            font-weight: 700;
                            margin-bottom: 0.75rem;
                        "
                    >
                        {{ $widget->translation->title }}
                    </h2>
                @endif

                @if ($widget->translation->content)
                    <p
                        class="ap-feature-list-description"
                        style="
                            color: var(--mosaic-on-surface-variant);
                            font-size: var(--mosaic-text-body-lg);
                            line-height: 1.6;
                        "
                    >
                        {!! strip_tags($widget->translation->content) !!}
                    </p>
                @endif
            </div>
        @endif

        @if ($layout === 'vertical')
            <div
                style="
                    display: flex;
                    flex-direction: column;
                    gap: 1.25rem;
                    max-width: 38rem;
                "
            >
                @forelse ($widget->assets as $widgetAsset)
                    <div
                        class="ap-feature-item mosaic-card"
                        style="
                            display: flex;
                            gap: 1rem;
                            background-color: var(--mosaic-surface-container);
                        "
                    >
                        @if ($widgetAsset->asset->getMeta('icon'))
                            <div style="flex-shrink: 0; font-size: 1.75rem">
                                {{ $widgetAsset->asset->getMeta('icon') }}
                            </div>
                        @endif

                        <div>
                            @if ($widgetAsset->asset->translation?->title)
                                <h3
                                    class="ap-feature-title"
                                    style="
                                        color: var(--mosaic-on-surface);
                                        font-size: var(--mosaic-text-title-md);
                                        font-weight: 600;
                                        margin-bottom: 0.375rem;
                                    "
                                >
                                    {{ $widgetAsset->asset->translation->title }}
                                </h3>
                            @endif

                            @if ($widgetAsset->asset->translation?->content)
                                <p
                                    class="ap-feature-description"
                                    style="
                                        color: var(--mosaic-on-surface-variant);
                                        font-size: var(--mosaic-text-body-sm);
                                        line-height: 1.55;
                                    "
                                >
                                    {{ strip_tags($widgetAsset->asset->translation->content) }}
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="color: var(--mosaic-on-surface-variant)">
                        No features configured.
                    </p>
                @endforelse
            </div>
        @else
            <div
                style="
                    display: grid;
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                    gap: 1.5rem;
                "
            >
                @forelse ($widget->assets as $widgetAsset)
                    <div
                        class="ap-feature-item mosaic-card"
                        style="
                            text-align: center;
                            background-color: var(--mosaic-surface-container);
                        "
                    >
                        @if ($widgetAsset->asset->getMeta('icon'))
                            <div
                                style="
                                    font-size: 2.25rem;
                                    margin-bottom: 0.75rem;
                                "
                            >
                                {{ $widgetAsset->asset->getMeta('icon') }}
                            </div>
                        @endif

                        @if ($widgetAsset->asset->translation?->title)
                            <h3
                                class="ap-feature-title"
                                style="
                                    color: var(--mosaic-on-surface);
                                    font-size: var(--mosaic-text-title-md);
                                    font-weight: 600;
                                    margin-bottom: 0.375rem;
                                "
                            >
                                {{ $widgetAsset->asset->translation->title }}
                            </h3>
                        @endif

                        @if ($widgetAsset->asset->translation?->content)
                            <p
                                class="ap-feature-description"
                                style="
                                    color: var(--mosaic-on-surface-variant);
                                    font-size: var(--mosaic-text-body-sm);
                                    line-height: 1.55;
                                "
                            >
                                {{ strip_tags($widgetAsset->asset->translation->content) }}
                            </p>
                        @endif
                    </div>
                @empty
                    <div
                        style="
                            grid-column: 1 / -1;
                            text-align: center;
                            padding: 3rem;
                            color: var(--mosaic-on-surface-variant);
                        "
                    >
                        No features configured.
                    </div>
                @endforelse
            </div>
        @endif
    </section>
</x-capell-mosaic::widget.wrapper>
