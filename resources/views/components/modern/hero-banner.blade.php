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

@php
    $backgroundImage = $widget->backgroundImage ?? $widget->image;
@endphp

<x-capell-mosaic::widget.wrapper
    class="widget-ap-hero-banner"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section
        class="ap-hero relative overflow-hidden"
        style="
            min-height: 480px;
            background-color: var(--mosaic-background);
            {{ $backgroundImage ? "background-image: url('{$backgroundImage->getFullUrl()}'); background-size: cover; background-position: center;" : '' }}
        "
    >
        <div
            class="absolute inset-0"
            style="
                background: linear-gradient(
                    135deg,
                    rgba(19, 19, 19, 0.92) 0%,
                    rgba(19, 19, 19, 0.7) 100%
                );
            "
        ></div>

        <div
            class="relative flex flex-col justify-center px-8 py-16 md:px-16"
            style="min-height: 480px"
        >
            <div style="max-width: 40rem">
                @if ($title)
                    <h1
                        class="ap-headline"
                        style="
                            color: var(--mosaic-on-surface);
                            font-family: var(--mosaic-font-headline);
                            font-size: var(--mosaic-text-display-md);
                            font-weight: 700;
                            letter-spacing: -0.02em;
                            margin-bottom: 1.25rem;
                        "
                    >
                        {{ $title }}
                    </h1>
                @endif

                @if ($content)
                    <p
                        style="
                            color: var(--mosaic-on-surface-variant);
                            font-size: var(--mosaic-text-body-lg);
                            line-height: 1.65;
                            margin-bottom: 2rem;
                        "
                    >
                        {!! strip_tags($content) !!}
                    </p>
                @endif

                @if ($primaryButtonText || $secondaryButtonText)
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap">
                        @if ($primaryButtonText)
                            <a
                                href="{{ $primaryButtonUrl }}"
                                class="mosaic-btn mosaic-btn-primary ap-cta-primary"
                            >
                                {{ $primaryButtonText }}
                            </a>
                        @endif

                        @if ($secondaryButtonText)
                            <a
                                href="{{ $secondaryButtonUrl }}"
                                class="mosaic-btn mosaic-btn-secondary ap-cta-secondary"
                            >
                                {{ $secondaryButtonText }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-capell-mosaic::widget.wrapper>
