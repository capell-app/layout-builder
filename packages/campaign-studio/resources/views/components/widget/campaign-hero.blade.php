@props([
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'eyebrow' => $widget->getMeta('eyebrow'),
    'primaryButtonText' => $widget->getMeta('primary_button_text'),
    'primaryButtonUrl' => $widget->getMeta('primary_button_url', '#'),
    'secondaryButtonText' => $widget->getMeta('secondary_button_text'),
    'secondaryButtonUrl' => $widget->getMeta('secondary_button_url', '#'),
    'goalKey' => $widget->getMeta('goal_key'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-layout-builder::widget.wrapper
    class="widget-campaign-hero"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section class="campaign-hero px-6 py-16">
        <div class="mx-auto max-w-5xl">
            @if ($eyebrow)
                <p class="mb-3 text-sm font-semibold uppercase tracking-wide">
                    {{ $eyebrow }}
                </p>
            @endif

            @if ($title)
                <h1 class="max-w-3xl text-4xl font-bold">{{ $title }}</h1>
            @endif

            @if ($content)
                <div class="mt-5 max-w-2xl text-lg">{!! $content !!}</div>
            @endif

            <div class="mt-8 flex flex-wrap gap-3">
                @if ($primaryButtonText)
                    <a
                        href="{{ $primaryButtonUrl }}"
                        class="layout-builder-btn layout-builder-btn-primary"
                        data-campaign-goal="{{ $goalKey }}"
                        data-campaign-location="hero-primary"
                    >
                        {{ $primaryButtonText }}
                    </a>
                @endif

                @if ($secondaryButtonText)
                    <a
                        href="{{ $secondaryButtonUrl }}"
                        class="layout-builder-btn layout-builder-btn-secondary"
                        data-campaign-location="hero-secondary"
                    >
                        {{ $secondaryButtonText }}
                    </a>
                @endif
            </div>
        </div>
    </section>
</x-capell-layout-builder::widget.wrapper>
