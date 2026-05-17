<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\ContentGraph\Extractors;

use Capell\Core\Contracts\ContentGraph\ContentGraphExtractor;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeCollectionData;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeData;
use Capell\Core\Data\ContentGraph\ContentGraphNodeData;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeKind;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeStrength;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Models\Element;
use Illuminate\Database\Eloquent\Model;

final class LayoutElementContentGraphExtractor implements ContentGraphExtractor
{
    public static function sourceModel(): string
    {
        return Layout::class;
    }

    public function extract(Model $model): ContentGraphEdgeCollectionData
    {
        if (! $model instanceof Layout) {
            return ContentGraphEdgeCollectionData::make();
        }

        $elementKeys = collect([
            ...$this->legacyElementKeys($model),
            ...$this->containerElementKeys($model),
        ])
            ->filter(fn (mixed $elementKey): bool => is_string($elementKey) || is_numeric($elementKey))
            ->map(fn (mixed $elementKey): string => (string) $elementKey)
            ->unique()
            ->values();

        if ($elementKeys->isEmpty()) {
            return ContentGraphEdgeCollectionData::make();
        }

        $source = ContentGraphNodeData::fromModel($model);
        $siteId = is_numeric($model->site_id) ? $model->site_id : null;
        $edges = [];

        Element::query()
            ->whereIn('key', $elementKeys)
            ->get()
            ->each(function (Element $element) use (&$edges, $source, $siteId): void {
                $edges[] = new ContentGraphEdgeData(
                    source: $source,
                    target: ContentGraphNodeData::fromModelIdentity(Element::class, (int) $element->getKey()),
                    kind: ContentGraphEdgeKind::UsesElement,
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
    private function legacyElementKeys(Layout $layout): array
    {
        return collect((array) $layout->getAttribute('elements'))
            ->flatten()
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function containerElementKeys(Layout $layout): array
    {
        return collect((array) $layout->containers)
            ->flatMap(function (mixed $container): array {
                if (! is_array($container)) {
                    return [];
                }

                $elements = $container['elements'] ?? null;

                if (! is_array($elements)) {
                    return [];
                }

                return collect($elements)
                    ->map(fn (mixed $element): mixed => is_array($element) ? ($element['element_key'] ?? $element['key'] ?? null) : $element)
                    ->all();
            })
            ->all();
    }
}
