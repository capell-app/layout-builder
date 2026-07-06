@props ([
    'widget',
    'containerKey',
    'widgetData' => [],
    'widgetAssets' => null,
    'widgetAssetsByWidget' => null,
    'renderableType' => 'section',
    'renderableKeyMeta' => 'kind',
    'defaultRenderableKey' => 'section',
    'implementation' => 'blade',
])

@php
    use Capell\LayoutBuilder\Contracts\Assets\PublicLayoutWidgetAssetsRenderer;

    $renderer = app()->bound(PublicLayoutWidgetAssetsRenderer::class)
        ? app(PublicLayoutWidgetAssetsRenderer::class)
        : null;
@endphp

@if ($renderer instanceof PublicLayoutWidgetAssetsRenderer)
    {!!
        $renderer->render(
            widget: $widget,
            containerKey: (string) $containerKey,
            widgetData: (array) $widgetData,
            widgetAssets: $widgetAssets,
            widgetAssetsByWidget: $widgetAssetsByWidget,
            options: [
                'renderableType' => $renderableType,
                'renderableKeyMeta' => $renderableKeyMeta,
                'defaultRenderableKey' => $defaultRenderableKey,
                'implementation' => $implementation,
            ],
        )
    !!}
@endif
