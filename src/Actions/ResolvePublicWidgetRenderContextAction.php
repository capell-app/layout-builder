<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Actions\Interactions\ResolveInteractionTriggersAction;
use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
use Capell\Core\Enums\PresentationDeliveryMode;
use Capell\Core\Models\Blueprint;
use Capell\Frontend\Contracts\PublicLayoutRenderPreparer;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\Actions\Fragments\BuildLayoutBuilderFragmentReferenceAction;
use Capell\LayoutBuilder\Data\PublicWidgetRenderContextData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\LayoutBuilderPublicLayoutRenderPreparer;
use Capell\LayoutBuilder\Support\Livewire\OpaqueWidgetReference;
use LogicException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static PublicWidgetRenderContextData run(?object $layout, string $containerKey, int|string $widgetIndex, Widget $widget, array<string, mixed> $widgetData, string $type, bool $hasPreparedHtml = false)
 */
final class ResolvePublicWidgetRenderContextAction
{
    use AsFake;
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
        bool $hasPreparedHtml = false,
    ): PublicWidgetRenderContextData {
        $occurrence = is_numeric($widgetData['occurrence'] ?? null) ? (int) $widgetData['occurrence'] : 1;
        $layoutKey = $this->layoutKey($layout);

        if (app()->bound(PublicLayoutRenderPreparer::class)) {
            $preparer = resolve(PublicLayoutRenderPreparer::class);
            $widgetKey = is_scalar($widgetData['widget_key'] ?? null) ? (string) $widgetData['widget_key'] : $widget->key;
            $prepared = $preparer instanceof LayoutBuilderPublicLayoutRenderPreparer && is_string($widgetKey)
                ? $preparer->context($layout, $containerKey, $widgetKey, $occurrence)
                : null;
            if ($prepared !== null) {
                return $prepared['index'] === $widgetIndex
                    ? $prepared['context']
                    : $this->reindexed($prepared['context'], $layoutKey, $containerKey, $widget, $widgetData, $widgetIndex, $type, $hasPreparedHtml);
            }
        }

        $widgetMeta = is_array($widgetData['meta'] ?? null) ? $widgetData['meta'] : [];
        $blueprint = $widget->relationLoaded('blueprint') ? $widget->getRelation('blueprint') : null;
        $blueprintMeta = $blueprint instanceof Blueprint && is_array($blueprint->meta) ? $blueprint->meta : [];
        $widgetReference = null;

        $presentation = ResolvePresentationSettingsAction::run(
            instanceSettings: is_array($widgetMeta['presentation'] ?? null) ? $widgetMeta['presentation'] : [],
            typeDefaults: is_array($blueprintMeta['presentation'] ?? null) ? $blueprintMeta['presentation'] : [],
        );
        $isLazyFragment = $presentation->deliveryMode === PresentationDeliveryMode::LazyFragment;
        // A prepared lazy placeholder already owns its reference and interactions.
        $preparedLazyFragment = $isLazyFragment && $hasPreparedHtml;

        $instanceInteractions = $this->triggersWithCurrentWidgetFragment(
            triggers: ! $preparedLazyFragment && is_array($widgetMeta['interactions'] ?? null) ? $widgetMeta['interactions'] : [],
            widgetReference: $widgetReference,
            containerKey: $containerKey,
            layoutKey: $layoutKey,
            occurrence: $occurrence,
            widget: $widget,
            widgetData: $widgetData,
            widgetIndex: $widgetIndex,
        );
        $typeDefaultInteractions = $this->triggersWithCurrentWidgetFragment(
            triggers: ! $preparedLazyFragment && is_array($blueprintMeta['interactions'] ?? null) ? $blueprintMeta['interactions'] : [],
            widgetReference: $widgetReference,
            containerKey: $containerKey,
            layoutKey: $layoutKey,
            occurrence: $occurrence,
            widget: $widget,
            widgetData: $widgetData,
            widgetIndex: $widgetIndex,
        );

        if ($isLazyFragment && ! $preparedLazyFragment) {
            $widgetReference ??= $this->fragmentReference(
                containerKey: $containerKey,
                occurrence: $occurrence,
                widget: $widget,
            );
        } elseif ($type === 'livewire' && ! $hasPreparedHtml) {
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
            widgetDomId: $this->widgetDomId($layoutKey, $containerKey, $widgetIndex),
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

    /**
     * The prepared data belongs to this exact widget and occurrence; only the
     * values derived from the caller's loop index are recomputed, without queries.
     *
     * @param  array<string, mixed>  $widgetData
     */
    private function reindexed(
        PublicWidgetRenderContextData $prepared,
        string $layoutKey,
        string $containerKey,
        Widget $widget,
        array $widgetData,
        int|string $widgetIndex,
        string $type,
        bool $hasPreparedHtml,
    ): PublicWidgetRenderContextData {
        $widgetReference = $prepared->widgetReference;
        if (! $prepared->isLazyFragment && $type === 'livewire' && ! $hasPreparedHtml) {
            $widgetReference = $this->widgetReference($containerKey, $layoutKey, $prepared->occurrence, $widget, $widgetData, $widgetIndex);
        }

        return new PublicWidgetRenderContextData(
            occurrence: $prepared->occurrence,
            widgetDomId: $this->widgetDomId($layoutKey, $containerKey, $widgetIndex),
            presentation: $prepared->presentation,
            isLazyFragment: $prepared->isLazyFragment,
            widgetReference: $widgetReference,
            resourcePublicIds: $prepared->resourcePublicIds,
            interactions: $prepared->interactions,
        );
    }

    private function widgetDomId(string $layoutKey, string $containerKey, int|string $widgetIndex): string
    {
        return 'layout-widget-' . hash('xxh128', $layoutKey . ':' . $containerKey . ':' . $widgetIndex);
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
            if (is_array($trigger)) {
                /** @var array<string, mixed> $trigger */
                $prepared[$triggerKey] = $this->withCurrentWidgetFragment($trigger, $widgetReference, $containerKey, $occurrence, $widget);
            } else {
                $prepared[$triggerKey] = $trigger;
            }
        }

        return $prepared;
    }

    /**
     * @param  array<string, mixed>  $trigger
     * @return array<string, mixed>
     */
    private function withCurrentWidgetFragment(
        array $trigger,
        ?string &$widgetReference,
        string $containerKey,
        int $occurrence,
        Widget $widget,
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

        $widgetReference ??= $this->fragmentReference($containerKey, $occurrence, $widget);

        if ($target !== null) {
            $trigger['target']['fragment_reference'] = $widgetReference;

            return $trigger;
        }

        $trigger['fragment_reference'] = $widgetReference;

        return $trigger;
    }

    private function fragmentReference(
        string $containerKey,
        int $occurrence,
        Widget $widget,
    ): string {
        $reference = BuildLayoutBuilderFragmentReferenceAction::run($containerKey, $occurrence, $widget);

        if (! is_string($reference) || $reference === '') {
            throw new LogicException('Public widget fragments require a complete frontend context.');
        }

        return $reference;
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
        $blueprint = $widget->relationLoaded('blueprint') ? $widget->getRelation('blueprint') : null;
        $blueprintMeta = $blueprint instanceof Blueprint && is_array($blueprint->meta) ? $blueprint->meta : [];
        $resourceGroups = collect([
            ...(is_array($blueprintMeta['resource_groups'] ?? null) ? $blueprintMeta['resource_groups'] : []),
            ...(is_array($widgetMeta['resource_groups'] ?? null) ? $widgetMeta['resource_groups'] : []),
        ])
            ->filter(static fn (mixed $resourceGroup): bool => is_string($resourceGroup) && $resourceGroup !== '')
            ->unique()
            ->values();

        return array_values($resourceGroups
            ->map(static fn (string $resourceGroup): string => LayoutBuilderLayoutWidgetResourceUsageContributor::publicId(
                is_scalar($widgetData['widget_key'] ?? null)
                    ? (string) $widgetData['widget_key']
                    : $widget->key,
                $resourceGroup,
                $containerKey,
                $occurrence,
            ))
            ->all());
    }
}
