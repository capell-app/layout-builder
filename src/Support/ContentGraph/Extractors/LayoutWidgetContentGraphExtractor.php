<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\ContentGraph\Extractors;

use Capell\Core\Contracts\ContentGraph\ContentGraphExtractor;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeCollectionData;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeData;
use Capell\Core\Data\ContentGraph\ContentGraphNodeData;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeStrength;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Model;

final class LayoutWidgetContentGraphExtractor implements ContentGraphExtractor
{
    private const string USES_LAYOUT_BLOCK = 'uses_layout_widget';

    public static function sourceModel(): string
    {
        return Layout::class;
    }

    public function extract(Model $model): ContentGraphEdgeCollectionData
    {
        if (! $model instanceof Layout) {
            return ContentGraphEdgeCollectionData::make();
        }

        $widgetKeys = collect($this->containerWidgetKeys($model))
            ->filter(fn (mixed $widgetKey): bool => is_string($widgetKey) || is_numeric($widgetKey))
            ->map(fn (mixed $widgetKey): string => (string) $widgetKey)
            ->unique()
            ->values();

        if ($widgetKeys->isEmpty()) {
            return ContentGraphEdgeCollectionData::make();
        }

        $source = ContentGraphNodeData::fromModel($model);
        $siteId = is_numeric($model->site_id) ? $model->site_id : null;
        $edges = [];

        Widget::query()
            ->whereIn('key', $widgetKeys)
            ->get()
            ->each(function (Widget $widget) use (&$edges, $source, $siteId): void {
                $edges[] = new ContentGraphEdgeData(
                    source: $source,
                    target: ContentGraphNodeData::fromModelIdentity(Widget::class, (int) $widget->getKey()),
                    kind: self::USES_LAYOUT_BLOCK,
                    strength: ContentGraphEdgeStrength::Strong,
                    sourcePackage: LayoutBuilderServiceProvider::$packageName,
                    siteId: $siteId,
                );
            });

        return ContentGraphEdgeCollectionData::make($edges);
    }

    /**
     * @return array<int, mixed>
     */
    private function containerWidgetKeys(Layout $layout): array
    {
        return collect((array) $layout->containers)
            ->flatMap(function (mixed $container): array {
                if (! is_array($container)) {
                    return [];
                }

                $widgets = $container['widgets'] ?? null;

                if (! is_array($widgets)) {
                    return [];
                }

                return collect($widgets)
                    ->map(fn (mixed $widget): mixed => is_array($widget) ? ($widget['widget_key'] ?? $widget['key'] ?? null) : $widget)
                    ->all();
            })
            ->all();
    }
}
