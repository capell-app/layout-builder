<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\ContentGraph\Extractors;

use Capell\Core\Contracts\ContentGraph\ContentGraphExtractor;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeCollectionData;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeData;
use Capell\Core\Data\ContentGraph\ContentGraphNodeData;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeKind;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeStrength;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Media;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Models\Block;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class BlockContentGraphExtractor implements ContentGraphExtractor
{
    public static function sourceModel(): string
    {
        return Block::class;
    }

    public function extract(Model $model): ContentGraphEdgeCollectionData
    {
        if (! $model instanceof Block) {
            return ContentGraphEdgeCollectionData::make();
        }

        $model->loadMissing(['assets', 'media']);

        $source = ContentGraphNodeData::fromModel($model);
        $edges = [];

        $mediaItems = $model->getRelationValue('media');
        if ($mediaItems instanceof Collection) {
            $mediaItems
                ->filter(fn (Media $media): bool => in_array($media->collection_name, [
                    MediaCollectionEnum::Image->value,
                    MediaCollectionEnum::BackgroundImage->value,
                ], true))
                ->each(function (Media $media) use (&$edges, $source): void {
                    $edges[] = $this->edge(
                        source: $source,
                        target: ContentGraphNodeData::fromModelIdentity(Media::class, (int) $media->getKey()),
                        kind: ContentGraphEdgeKind::UsesMedia,
                        strength: ContentGraphEdgeStrength::Strong,
                    );
                });
        }

        $assets = $model->getRelationValue('assets');
        if ($assets instanceof Collection) {
            $assets->each(function (Model $asset) use (&$edges, $source): void {
                $edges[] = $this->edge(
                    source: $source,
                    target: ContentGraphNodeData::fromModelIdentity($asset::class, (int) $asset->getKey()),
                    kind: ContentGraphEdgeKind::UsesBlock,
                    strength: ContentGraphEdgeStrength::Informational,
                );
            });
        }

        return ContentGraphEdgeCollectionData::make($edges);
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
