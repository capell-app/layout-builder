<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Actions\Interactions\ResolveInteractionTriggersAction;
use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
use Capell\Core\Enums\PresentationDeliveryMode;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\Data\PublicWidgetRenderContextData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\Livewire\OpaqueWidgetReference;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static PublicWidgetRenderContextData run(?object $layout, string $containerKey, int|string $widgetIndex, Widget $widget, array $widgetData, string $type)
 */
final class ResolvePublicWidgetRenderContextAction
{
    use AsObject;

    /**
     * @param  array<string, mixed>  $widgetData
     */
    public function handle(
        ?object $layout,
        string $containerKey,
        int|string $widgetIndex,
        Widget $widget,
        array $widgetData,
        string $type,
    ): PublicWidgetRenderContextData {
        $occurrence = is_numeric($widgetData['occurrence'] ?? null) ? (int) $widgetData['occurrence'] : 1;
        $layoutKey = $this->layoutKey($layout);
        $widgetMeta = is_array($widgetData['meta'] ?? null) ? $widgetData['meta'] : [];
        $widgetReference = null;

        $presentation = ResolvePresentationSettingsAction::run(
            instanceSettings: is_array($widgetMeta['presentation'] ?? null) ? $widgetMeta['presentation'] : [],
            typeDefaults: is_array($widget->blueprint?->meta['presentation'] ?? null) ? $widget->blueprint->meta['presentation'] : [],
        );
        $isLazyFragment = $presentation->deliveryMode === PresentationDeliveryMode::LazyFragment;

        $instanceInteractions = $this->triggersWithCurrentWidgetFragment(
            triggers: is_array($widgetMeta['interactions'] ?? null) ? $widgetMeta['interactions'] : [],
            widgetReference: $widgetReference,
            containerKey: $containerKey,
            layoutKey: $layoutKey,
            occurrence: $occurrence,
            widget: $widget,
            widgetData: $widgetData,
            widgetIndex: $widgetIndex,
        );
        $typeDefaultInteractions = $this->triggersWithCurrentWidgetFragment(
            triggers: is_array($widget->blueprint?->meta['interactions'] ?? null) ? $widget->blueprint->meta['interactions'] : [],
            widgetReference: $widgetReference,
            containerKey: $containerKey,
            layoutKey: $layoutKey,
            occurrence: $occurrence,
            widget: $widget,
            widgetData: $widgetData,
            widgetIndex: $widgetIndex,
        );

        if ($isLazyFragment || $type === 'livewire') {
            $widgetReference ??= $this->widgetReference(
                containerKey: $containerKey,
                layoutKey: $layoutKey,
                occurrence: $occurrence,
                widget: $widget,
                widgetData: $widgetData,
                widgetIndex: $widgetIndex,
            );
        }

        return new PublicWidgetRenderContextData(
            occurrence: $occurrence,
            widgetDomId: 'layout-widget-' . hash('xxh128', $layoutKey . ':' . $containerKey . ':' . (string) $widgetIndex),
            presentation: $presentation,
            isLazyFragment: $isLazyFragment,
            widgetReference: $widgetReference,
            resourcePublicIds: $this->resourcePublicIds($widget, $widgetData, $containerKey, $occurrence, $widgetMeta),
            interactions: ResolveInteractionTriggersAction::run(
                instanceTriggers: $instanceInteractions,
                typeDefaultTriggers: $typeDefaultInteractions,
            ),
        );
    }

    private function layoutKey(?object $layout): string
    {
        if (! is_object($layout) || ! method_exists($layout, 'getKey')) {
            return 'global';
        }

        $key = $layout->getKey();

        return is_scalar($key) && (string) $key !== '' ? (string) $key : 'global';
    }

    /**
     * @param  array<int|string, mixed>  $triggers
     * @param  array<string, mixed>  $widgetData
     * @return array<int|string, mixed>
     */
    private function triggersWithCurrentWidgetFragment(
        array $triggers,
        ?string &$widgetReference,
        string $containerKey,
        string $layoutKey,
        int $occurrence,
        Widget $widget,
        array $widgetData,
        int|string $widgetIndex,
    ): array {
        $prepared = [];

        foreach ($triggers as $triggerKey => $trigger) {
            $prepared[$triggerKey] = is_array($trigger)
                ? $this->withCurrentWidgetFragment($trigger, $widgetReference, $containerKey, $layoutKey, $occurrence, $widget, $widgetData, $widgetIndex)
                : $trigger;
        }

        return $prepared;
    }

    /**
     * @param  array<string, mixed>  $trigger
     * @param  array<string, mixed>  $widgetData
     * @return array<string, mixed>
     */
    private function withCurrentWidgetFragment(
        array $trigger,
        ?string &$widgetReference,
        string $containerKey,
        string $layoutKey,
        int $occurrence,
        Widget $widget,
        array $widgetData,
        int|string $widgetIndex,
    ): array {
        $target = is_array($trigger['target'] ?? null) ? $trigger['target'] : null;
        $targetType = $trigger['target_type'] ?? $target['target_type'] ?? null;

        if ($targetType !== 'fragment') {
            return $trigger;
        }

        $fragmentReference = $trigger['fragment_reference'] ?? $target['fragment_reference'] ?? null;

        if (is_string($fragmentReference) && trim($fragmentReference) !== '') {
            return $trigger;
        }

        $widgetReference ??= $this->widgetReference($containerKey, $layoutKey, $occurrence, $widget, $widgetData, $widgetIndex);

        if ($target !== null) {
            $trigger['target']['fragment_reference'] = $widgetReference;

            return $trigger;
        }

        $trigger['fragment_reference'] = $widgetReference;

        return $trigger;
    }

    /**
     * @param  array<string, mixed>  $widgetData
     */
    private function widgetReference(
        string $containerKey,
        string $layoutKey,
        int $occurrence,
        Widget $widget,
        array $widgetData,
        int|string $widgetIndex,
    ): string {
        return OpaqueWidgetReference::encode([
            'container_key' => $containerKey,
            'widget_key' => $widgetData['widget_key'] ?? $widget->key,
            'layout_id' => $layoutKey === 'global' ? null : $layoutKey,
            'language_id' => Frontend::language()?->getKey(),
            'occurrence' => $occurrence,
            'page_id' => Frontend::page()?->getKey(),
            'page_type' => Frontend::page()?->getMorphClass(),
            'site_id' => Frontend::site()?->getKey(),
            'widget_index' => $widgetIndex,
        ]);
    }

    /**
     * @param  array<string, mixed>  $widgetData
     * @param  array<string, mixed>  $widgetMeta
     * @return list<string>
     */
    private function resourcePublicIds(Widget $widget, array $widgetData, string $containerKey, int $occurrence, array $widgetMeta): array
    {
        $resourceGroups = collect([
            ...(is_array($widget->blueprint?->meta['resource_groups'] ?? null) ? $widget->blueprint->meta['resource_groups'] : []),
            ...(is_array($widgetMeta['resource_groups'] ?? null) ? $widgetMeta['resource_groups'] : []),
        ])
            ->filter(static fn (mixed $resourceGroup): bool => is_string($resourceGroup) && $resourceGroup !== '')
            ->unique()
            ->values();

        return $resourceGroups
            ->map(static fn (string $resourceGroup): string => LayoutBuilderLayoutWidgetResourceUsageContributor::publicId(
                (string) ($widgetData['widget_key'] ?? $widget->key),
                $resourceGroup,
                $containerKey,
                $occurrence,
            ))
            ->all();
    }
}
