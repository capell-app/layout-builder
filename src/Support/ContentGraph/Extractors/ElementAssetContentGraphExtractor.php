<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\ContentGraph\Extractors;

use Capell\Core\Contracts\ContentGraph\ContentGraphExtractor;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeCollectionData;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeData;
use Capell\Core\Data\ContentGraph\ContentGraphNodeData;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeKind;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeStrength;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

final class ElementAssetContentGraphExtractor implements ContentGraphExtractor
{
    public static function sourceModel(): string
    {
        return ElementAsset::class;
    }

    public function extract(Model $model): ContentGraphEdgeCollectionData
    {
        if (! $model instanceof ElementAsset) {
            return ContentGraphEdgeCollectionData::make();
        }

        $source = ContentGraphNodeData::fromModel($model);
        $edges = [];

        if ($model->element_id !== null) {
            $edges[] = $this->edge(
                source: $source,
                target: ContentGraphNodeData::fromModelIdentity(Element::class, $model->element_id),
                kind: ContentGraphEdgeKind::UsesElement,
                strength: ContentGraphEdgeStrength::Strong,
            );
        }

        foreach ($this->linkedPageTargets($model) as $pageId) {
            $edges[] = $this->edge(
                source: $source,
                target: ContentGraphNodeData::fromModelIdentity(Page::class, $pageId),
                kind: ContentGraphEdgeKind::LinksToPage,
                strength: ContentGraphEdgeStrength::Strong,
            );
        }

        $assetType = $this->resolveMorphType($model->asset_type);
        if ($assetType === Media::class && is_numeric($model->asset_id)) {
            $edges[] = $this->edge(
                source: $source,
                target: ContentGraphNodeData::fromModelIdentity(Media::class, (int) $model->asset_id),
                kind: ContentGraphEdgeKind::UsesMedia,
                strength: ContentGraphEdgeStrength::Strong,
            );
        }

        return ContentGraphEdgeCollectionData::make($edges);
    }

    /**
     * @return array<int, int>
     */
    private function linkedPageTargets(ElementAsset $model): array
    {
        $pageIds = [];

        if ($this->resolveMorphType($model->pageable_type) === Page::class && is_numeric($model->pageable_id)) {
            $pageIds[] = $model->pageable_id;
        }

        if ($this->resolveMorphType($model->asset_type) === Page::class && is_numeric($model->asset_id)) {
            $pageIds[] = $model->asset_id;
        }

        if ($this->resolveMorphType((string) data_get($model->meta, 'linked_pageable_type')) === Page::class) {
            $linkedPageId = data_get($model->meta, 'linked_pageable_id');
            if (is_numeric($linkedPageId)) {
                $pageIds[] = (int) $linkedPageId;
            }
        }

        return array_values(array_unique($pageIds));
    }

    /**
     * @return class-string<Model>|null
     */
    private function resolveMorphType(?string $targetType): ?string
    {
        if ($targetType === null || $targetType === '') {
            return null;
        }

        $modelClass = Relation::getMorphedModel($targetType) ?? $targetType;

        if (! is_a($modelClass, Model::class, true)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        return $modelClass;
    }

    private function edge(
        ContentGraphNodeData $source,
        ContentGraphNodeData $target,
        ContentGraphEdgeKind $kind,
        ContentGraphEdgeStrength $strength,
    ): ContentGraphEdgeData {
        return new ContentGraphEdgeData(
            source: $source,
            target: $target,
            kind: $kind,
            strength: $strength,
            sourcePackage: LayoutBuilderServiceProvider::$packageName,
        );
    }
}
