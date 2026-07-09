@props ([
    'settings',
    'resourcePublicIds' => [],
])

@php
    use Capell\Frontend\Actions\RenderWidgetRuntimeAttributesAction;

    $runtimeAttributes = RenderWidgetRuntimeAttributesAction::run($settings);
    $runtimeJson = json_encode($runtimeAttributes['data'], JSON_THROW_ON_ERROR);
    $resourceIds = array_values(array_filter($resourcePublicIds, 'is_string'));
    $resourceJson = $resourceIds === [] ? null : json_encode($resourceIds, JSON_THROW_ON_ERROR);
@endphp

<div
    data-capell-widget-runtime
    data-capell-widget-settings="{{ $runtimeJson }}"
    @if ($resourceJson !== null)
        data-capell-widget-resources="{{ $resourceJson }}"
    @endif
    class="{{ $runtimeAttributes['class'] }}"
    @if ($runtimeAttributes['style'] !== '')
        style="{{ $runtimeAttributes['style'] }}"
    @endif
>
    {{ $slot }}
</div>
