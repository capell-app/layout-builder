@props([
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'columns' => (int) ($widget->getMeta('columns', 3)),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-mosaic::widget.wrapper
    class="widget-ap-image-gallery"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section
        style="padding: 3rem 2rem; background-color: var(--mosaic-surface)"
    >
        @if ($title || $content)
            <div style="margin-bottom: 2rem; max-width: 38rem">
                @if ($title)
                    <h2
                        class="ap-gallery-headline"
                        style="
                            color: var(--mosaic-on-surface);
                            font-family: var(--mosaic-font-headline);
                            font-size: var(--mosaic-text-headline-lg);
                            font-weight: 700;
                            margin-bottom: 0.75rem;
                        "
                    >
                        {{ $title }}
                    </h2>
                @endif

                @if ($content)
                    <p
                        class="ap-gallery-description"
                        style="
                            color: var(--mosaic-on-surface-variant);
                            font-size: var(--mosaic-text-body-lg);
                            line-height: 1.6;
                        "
                    >
                        {!! strip_tags($content) !!}
                    </p>
                @endif
            </div>
        @endif

        @if ($widget->assets->isNotEmpty())
            <div
                style="
                    display: grid;
                    grid-template-columns: repeat(
                        {{ $columns }},
                        minmax(0, 1fr)
                    );
                    gap: 1rem;
                "
            >
                @foreach ($widget->assets as $asset)
                    @php
                        $media = $asset->media->first() ?: $asset->asset->media->first();
                    @endphp

                    @if ($media)
                        <div
                            class="ap-gallery-item"
                            style="
                                overflow: hidden;
                                background-color: var(
                                    --mosaic-surface-container
                                );
                            "
                        >
                            <img
                                src="{{ $media->getFullUrl() }}"
                                alt="{{ $media->name }}"
                                style="
                                    width: 100%;
                                    height: 200px;
                                    object-fit: cover;
                                    display: block;
                                "
                            />
                        </div>
                    @endif
                @endforeach
            </div>
        @elseif ($widget->image)
            <div
                style="
                    display: grid;
                    grid-template-columns: repeat(
                        {{ $columns }},
                        minmax(0, 1fr)
                    );
                    gap: 1rem;
                "
            >
                <div
                    class="ap-gallery-item"
                    style="
                        overflow: hidden;
                        background-color: var(--mosaic-surface-container);
                    "
                >
                    <img
                        src="{{ $widget->image->getFullUrl() }}"
                        alt="{{ $widget->image->name }}"
                        style="
                            width: 100%;
                            height: 200px;
                            object-fit: cover;
                            display: block;
                        "
                    />
                </div>
            </div>
        @else
            <div
                style="
                    padding: 3rem;
                    text-align: center;
                    color: var(--mosaic-on-surface-variant);
                "
            >
                No images configured.
            </div>
        @endif
    </section>
</x-capell-mosaic::widget.wrapper>
