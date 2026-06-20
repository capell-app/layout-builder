@php
    use Capell\Core\Actions\Interactions\ResolveInteractionTriggersAction;
    use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
    use Capell\Core\Enums\PresentationDeliveryMode;
    use Capell\Frontend\Facades\Frontend;
    use Capell\LayoutBuilder\Support\LayoutWidgetResourceUsageContributor;
    use Capell\LayoutBuilder\Support\Livewire\OpaqueWidgetReference;

    $widgetComponent = $component;
    $occurrence = $widgetData['occurrence'] ?? 1;
    $layoutKey = is_object($layout) && method_exists($layout, 'getKey') ? $layout->getKey() : 'global';
    $widgetDomId = 'layout-widget-' . hash('xxh128', (string) $layoutKey . ':' . (string) $containerKey . ':' . (string) $widgetIndex);
    $widgetMeta = is_array($widgetData['meta'] ?? null) ? $widgetData['meta'] : [];
    $presentation = ResolvePresentationSettingsAction::run(
        instanceSettings: is_array($widgetMeta['presentation'] ?? null) ? $widgetMeta['presentation'] : [],
        typeDefaults: is_array($widget->type?->meta['presentation'] ?? null) ? $widget->type->meta['presentation'] : [],
    );
    $isLazyFragment = $presentation->deliveryMode === PresentationDeliveryMode::LazyFragment;
    $widgetReference = null;
    $makeWidgetReference = function () use (&$widgetReference, $containerKey, $layoutKey, $occurrence, $widget, $widgetData, $widgetIndex): string {
        if ($widgetReference === null) {
            $widgetReference = OpaqueWidgetReference::encode([
                'container_key' => $containerKey,
                'widget_key' => $widgetData['widget_key'] ?? $widget->key,
                'layout_id' => $layoutKey === 'global' ? null : $layoutKey,
                'language_id' => Frontend::language()?->getKey(),
                'occurrence' => $occurrence,
                'page_id' => Frontend::page()?->getKey(),
                'page_type' => Frontend::page()?->getMorphClass(),
                'site_id' => Frontend::site()?->getKey(),
                'widget_data' => $widgetData,
                'widget_index' => $widgetIndex,
            ]);
        }

        return $widgetReference;
    };
    $withCurrentWidgetFragment = function (array $trigger) use ($makeWidgetReference): array {
        if (($trigger['target_type'] ?? $trigger['target']['target_type'] ?? null) !== 'fragment') {
            return $trigger;
        }

        if (filled($trigger['fragment_reference'] ?? $trigger['target']['fragment_reference'] ?? null)) {
            return $trigger;
        }

        $trigger['fragment_reference'] = $makeWidgetReference();

        return $trigger;
    };
    $instanceInteractions = collect(is_array($widgetMeta['interactions'] ?? null) ? $widgetMeta['interactions'] : [])
        ->map(fn (mixed $trigger): mixed => is_array($trigger) ? $withCurrentWidgetFragment($trigger) : $trigger)
        ->all();
    $typeDefaultInteractions = collect(is_array($widget->type?->meta['interactions'] ?? null) ? $widget->type->meta['interactions'] : [])
        ->map(fn (mixed $trigger): mixed => is_array($trigger) ? $withCurrentWidgetFragment($trigger) : $trigger)
        ->all();
    $interactions = ResolveInteractionTriggersAction::run(
        instanceTriggers: $instanceInteractions,
        typeDefaultTriggers: $typeDefaultInteractions,
    );
    $resourceGroups = collect([
        ...(is_array($widget->type?->meta['resource_groups'] ?? null) ? $widget->type->meta['resource_groups'] : []),
        ...(is_array($widgetMeta['resource_groups'] ?? null) ? $widgetMeta['resource_groups'] : []),
    ])
        ->filter(fn (mixed $resourceGroup): bool => is_string($resourceGroup) && $resourceGroup !== '')
        ->unique()
        ->values()
        ->all();
    $resourcePublicIds = collect($resourceGroups)
        ->map(fn (string $resourceGroup): string => LayoutWidgetResourceUsageContributor::publicId(
            (string) ($widgetData['widget_key'] ?? $widget->key),
            $resourceGroup,
            (string) $containerKey,
            (int) $occurrence,
        ))
        ->all();

    if ($isLazyFragment || $type === 'livewire') {
        $widgetReference = $makeWidgetReference();
    }
@endphp

@if ($isLazyFragment)
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
