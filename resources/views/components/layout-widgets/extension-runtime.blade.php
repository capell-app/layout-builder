@props([
    'settings',
    'resourcePublicIds' => [],
    'resourceRuntimes' => [],
    'html' => '',
])

<x-capell-layout-builder::layout-widgets.runtime-wrapper
    :settings="$settings"
    :resource-public-ids="$resourcePublicIds"
>
    @foreach ($resourceRuntimes as $runtime)
        <div
            data-capell-widget-runtime
            data-capell-widget-settings="{{ json_encode($runtime['settings']->publicRuntimePayload(), JSON_THROW_ON_ERROR) }}"
            data-capell-widget-resources="{{ json_encode($runtime['ids'], JSON_THROW_ON_ERROR) }}"
        >
    @endforeach

    {!! $html !!}

    @foreach ($resourceRuntimes as $runtime)
        </div>
    @endforeach
</x-capell-layout-builder::layout-widgets.runtime-wrapper>
