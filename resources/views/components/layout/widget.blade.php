@php
    use Capell\LayoutBuilder\Actions\ResolvePublicWidgetRenderContextAction;

    $widgetComponent = $component;
    $renderContext = ResolvePublicWidgetRenderContextAction::run(
        layout: is_object($layout) ? $layout : null,
        containerKey: (string) $containerKey,
        widgetIndex: $widgetIndex,
        widget: $widget,
        widgetData: $widgetData,
        type: $type,
    );
    $occurrence = $renderContext->occurrence;
    $widgetDomId = $renderContext->widgetDomId;
    $presentation = $renderContext->presentation;
    $isLazyFragment = $renderContext->isLazyFragment;
    $widgetReference = $renderContext->widgetReference;
    $fragmentUrl = $renderContext->fragmentUrl;
    $resourcePublicIds = $renderContext->resourcePublicIds;
    $interactions = $renderContext->interactions;
@endphp

@if ($isLazyFragment && $fragmentUrl !== null)
    <div
        id="{{ $widgetDomId }}"
        data-deferred-fragment
        data-deferred-fragment-url="{{ $fragmentUrl }}"
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
            @livewire ($widgetComponent, ['widgetReference' => $widgetReference], key($containerKey . '-' . $widget->key . '-' . $occurrence))
        </div>
        <x-capell::interactions :triggers="$interactions" />
    </x-capell-layout-builder::layout-widgets.runtime-wrapper>
@endif
