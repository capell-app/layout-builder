@props([
    'columns' => (int) ($widget->getMeta('columns', 3)),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-layout-builder::widget.wrapper
    class="widget-ap-card-grid"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section
        style="padding: 3rem 2rem; background-color: var(--layout-builder-surface)"
    >
        @if ($widget->translation)
            <div style="margin-bottom: 2.5rem; max-width: 38rem">
                @if ($widget->translation->title)
                    <h2
                        class="ap-card-grid-headline"
                        style="
                            color: var(--layout-builder-on-surface);
                            font-family: var(--layout-builder-font-headline);
                            font-size: var(--layout-builder-text-headline-lg);
                            font-weight: 700;
                            margin-bottom: 0.75rem;
                        "
                    >
                        {{ $widget->translation->title }}
                    </h2>
                @endif

                @if ($widget->translation->content)
                    <p
                        class="ap-card-grid-description"
                        style="
                            color: var(--layout-builder-on-surface-variant);
                            font-size: var(--layout-builder-text-body-lg);
                            line-height: 1.6;
                        "
                    >
                        {!! strip_tags($widget->translation->content) !!}
                    </p>
                @endif
            </div>
        @endif

        <div
            style="
                display: grid;
                grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));
                gap: 1.5rem;
            "
        >
            @if ($widget->assets->isNotEmpty())
                @foreach ($widget->assets as $widgetAsset)
                    <div
                        class="ap-card layout-builder-card"
                        style="
                            background-color: var(--layout-builder-surface-container);
                        "
                    >
                        @if ($widgetAsset->asset->getMeta('icon'))
                            <div style="font-size: 2rem; margin-bottom: 1rem">
                                {{ $widgetAsset->asset->getMeta('icon') }}
                            </div>
                        @endif

                        @if ($widgetAsset->asset->translation?->title)
                            <h3
                                class="ap-card-title"
                                style="
                                    color: var(--layout-builder-on-surface);
                                    font-size: var(--layout-builder-text-title-lg);
                                    font-weight: 600;
                                    margin-bottom: 0.5rem;
                                "
                            >
                                {{ $widgetAsset->asset->translation->title }}
                            </h3>
                        @endif

                        @if ($widgetAsset->asset->translation?->content)
                            <p
                                class="ap-card-description"
                                style="
                                    color: var(--layout-builder-on-surface-variant);
                                    font-size: var(--layout-builder-text-body-md);
                                    line-height: 1.55;
                                "
                            >
                                {{ strip_tags($widgetAsset->asset->translation->content) }}
                            </p>
                        @endif

                        @if ($widgetAsset->asset->getMeta('link_text') && $widgetAsset->asset->getMeta('link_url'))
                            <a
                                href="{{ $widgetAsset->asset->getMeta('link_url') }}"
                                class="ap-card-link"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    gap: 0.375rem;
                                    margin-top: 1rem;
                                    font-size: var(--layout-builder-text-body-sm);
                                    font-weight: 600;
                                    color: var(--layout-builder-primary);
                                    text-decoration: none;
                                "
                            >
                                {{ $widgetAsset->asset->getMeta('link_text') }}
                                →
                            </a>
                        @endif
                    </div>
                @endforeach
            @elseif ($widget->getMeta('cards'))
                @foreach ($widget->getMeta('cards') as $card)
                    <div
                        class="ap-card layout-builder-card"
                        style="
                            background-color: var(--layout-builder-surface-container);
                        "
                    >
                        @if (! empty($card['icon']))
                            <div style="font-size: 2rem; margin-bottom: 1rem">
                                {{ $card['icon'] }}
                            </div>
                        @endif

                        @if (! empty($card['title']))
                            <h3
                                class="ap-card-title"
                                style="
                                    color: var(--layout-builder-on-surface);
                                    font-size: var(--layout-builder-text-title-lg);
                                    font-weight: 600;
                                    margin-bottom: 0.5rem;
                                "
                            >
                                {{ $card['title'] }}
                            </h3>
                        @endif

                        @if (! empty($card['description']))
                            <p
                                class="ap-card-description"
                                style="
                                    color: var(--layout-builder-on-surface-variant);
                                    font-size: var(--layout-builder-text-body-md);
                                    line-height: 1.55;
                                "
                            >
                                {{ $card['description'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            @else
                <div
                    style="
                        grid-column: 1 / -1;
                        text-align: center;
                        padding: 3rem;
                        color: var(--layout-builder-on-surface-variant);
                    "
                >
                    No cards configured.
                </div>
            @endif
        </div>
    </section>
</x-capell-layout-builder::widget.wrapper>
