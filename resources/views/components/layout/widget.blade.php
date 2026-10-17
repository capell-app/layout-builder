@php
    use Capell\Frontend\Contracts\PublicLayoutRenderPreparer;
    use Capell\Frontend\Data\PublicPageRenderData;
    use Capell\LayoutBuilder\Actions\ResolvePublicWidgetRenderContextAction;
    use Capell\LayoutBuilder\Support\LayoutBuilderPublicLayoutRenderPreparer;
    use Capell\LayoutBuilder\Support\LayoutWidgetData;

    $widgetComponent = $component;
    $widgetKey ??= LayoutWidgetData::key($widgetData) ?? $widget->key;
    if (! isset($publicRenderData) && app()->bound(PublicLayoutRenderPreparer::class)) {
        $preparer = resolve(PublicLayoutRenderPreparer::class);
        $publicRenderData = $preparer instanceof LayoutBuilderPublicLayoutRenderPreparer
            ? $preparer->publicRenderData(is_object($layout) ? $layout : null)
            : null;
    }
    $preparedWidgetHtml = ($publicRenderData ?? null) instanceof PublicPageRenderData && method_exists($publicRenderData, 'preparedWidgetHtml')
        ? $publicRenderData->preparedWidgetHtml((string) $containerKey, $widgetKey, LayoutWidgetData::occurrence($widgetData))
        : null;
    $renderContext = ResolvePublicWidgetRenderContextAction::run(
        layout: is_object($layout) ? $layout : null,
        containerKey: (string) $containerKey,
        widgetIndex: $widgetIndex,
        widget: $widget,
        widgetData: $widgetData,
        type: $type,
        hasPreparedHtml: $preparedWidgetHtml !== null,
    );
    $occurrence = $renderContext->occurrence;
    $widgetDomId = $renderContext->widgetDomId;
    $presentation = $renderContext->presentation;
    $isLazyFragment = $renderContext->isLazyFragment;
    $widgetReference = $renderContext->widgetReference;
    $resourcePublicIds = $renderContext->resourcePublicIds;
    $interactions = $renderContext->interactions;
@endphp

@if ($preparedWidgetHtml !== null)
    <x-capell-layout-builder::layout-widgets.runtime-wrapper
        :settings="$presentation"
        :resource-public-ids="$resourcePublicIds"
    >
        @if ($isLazyFragment)
            {!! $preparedWidgetHtml !!}
        @else
            <div
                id="{{ $widgetDomId }}"
                class="layout-widget"
            >
                {!! $preparedWidgetHtml !!}
                <x-capell::interactions :triggers="$interactions" />
            </div>
        @endif
    </x-capell-layout-builder::layout-widgets.runtime-wrapper>
@elseif ($isLazyFragment)
    <div
        id="{{ $widgetDomId }}"
        data-deferred-fragment
        data-deferred-fragment-url="{{ url('/_fragments/' . rawurlencode($widgetReference)) }}"
        class="deferred-fragment"
    ></div>
@elseif ($type === 'blade')
    <x-capell-layout-builder::layout-widgets.runtime-wrapper
        :settings="$presentation"
        :resource-public-ids="$resourcePublicIds"
    >
        <div
            id="{{ $widgetDomId }}"
            class="layout-widget"
        >
            <x-dynamic-component
                :component="$widgetComponent"
                :$container
                :$containerColspan
                :$containerKey
                :$containerIndex
                :$containerWidth
                :$widget
                :$widgetData
                :$widgetIndex
                :$loop
                :$occurrence
                :$pageSlot
            />
            <x-capell::interactions :triggers="$interactions" />
        </div>
    </x-capell-layout-builder::layout-widgets.runtime-wrapper>
@elseif ($type === 'livewire')
    <x-capell-layout-builder::layout-widgets.runtime-wrapper
        :settings="$presentation"
        :resource-public-ids="$resourcePublicIds"
    >
        <div
            id="{{ $widgetDomId }}"
            class="layout-widget"
        >
            @livewire($widgetComponent, ['widgetReference' => $widgetReference], key($containerKey . '-' . $widget->key . '-' . $occurrence))
        </div>
        <x-capell::interactions :triggers="$interactions" />
    </x-capell-layout-builder::layout-widgets.runtime-wrapper>
@endif
