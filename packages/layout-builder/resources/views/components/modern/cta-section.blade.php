@props([
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'primaryButtonText' => $widget->getMeta('primary_button_text'),
    'primaryButtonUrl' => $widget->getMeta('primary_button_url', '#'),
    'secondaryButtonText' => $widget->getMeta('secondary_button_text'),
    'secondaryButtonUrl' => $widget->getMeta('secondary_button_url', '#'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-layout-builder::widget.wrapper
    class="widget-ap-cta-section"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section
        style="
            padding: 4rem 2rem;
            text-align: center;
            background-color: var(--layout-builder-surface-container);
            border-top: 1px solid var(--layout-builder-outline-variant);
        "
    >
        <div style="max-width: 36rem; margin: 0 auto">
            @if ($title)
                <h2
                    class="ap-cta-headline"
                    style="
                        color: var(--layout-builder-on-surface);
                        font-family: var(--layout-builder-font-headline);
                        font-size: var(--layout-builder-text-headline-lg);
                        font-weight: 700;
                        margin-bottom: 1rem;
                    "
                >
                    {{ $title }}
                </h2>
            @endif

            @if ($content)
                <p
                    class="ap-cta-description"
                    style="
                        color: var(--layout-builder-on-surface-variant);
                        font-size: var(--layout-builder-text-body-lg);
                        line-height: 1.65;
                        margin-bottom: 2rem;
                    "
                >
                    {!! strip_tags($content) !!}
                </p>
            @endif

            @if ($primaryButtonText || $secondaryButtonText)
                <div
                    style="
                        display: flex;
                        gap: 1rem;
                        justify-content: center;
                        flex-wrap: wrap;
                    "
                >
                    @if ($primaryButtonText)
                        <a
                            href="{{ $primaryButtonUrl }}"
                            class="layout-builder-btn layout-builder-btn-primary ap-cta-primary-btn"
                        >
                            {{ $primaryButtonText }}
                        </a>
                    @endif

                    @if ($secondaryButtonText)
                        <a
                            href="{{ $secondaryButtonUrl }}"
                            class="layout-builder-btn layout-builder-btn-secondary ap-cta-secondary-btn"
                        >
                            {{ $secondaryButtonText }}
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </section>
</x-capell-layout-builder::widget.wrapper>
