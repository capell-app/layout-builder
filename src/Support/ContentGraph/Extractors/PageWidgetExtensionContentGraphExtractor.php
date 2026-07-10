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
use Capell\LayoutBuilder\Actions\WidgetExtensions\ResolveWidgetExtensionDependenciesAction;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Illuminate\Database\Eloquent\Model;

final readonly class PageWidgetExtensionContentGraphExtractor implements ContentGraphExtractor
{
    public function __construct(
        private ResolveWidgetExtensionDependenciesAction $dependencies,
    ) {}

    public static function sourceModel(): string
    {
        return Page::class;
    }

    public function extract(Model $model): ContentGraphEdgeCollectionData
    {
        if (! $model instanceof Page) {
            return ContentGraphEdgeCollectionData::make();
        }

        $model->loadMissing('translations');
        $sources = $model->translations
            ->map(fn (Model $translation): mixed => $translation->getAttribute('content'))
            ->filter(fn (mixed $content): bool => is_array($content))
            ->values()
            ->all();
        $source = ContentGraphNodeData::fromModel($model);

        return ContentGraphEdgeCollectionData::make(collect($this->dependencies->resolve($sources))
            ->map(fn ($dependency): ContentGraphEdgeData => new ContentGraphEdgeData(
                source: $source,
                target: ContentGraphNodeData::fromModelIdentity($dependency->modelType, $dependency->modelId),
                kind: $dependency->modelType === Media::class ? ContentGraphEdgeKind::UsesMedia : $dependency->kind,
                strength: ContentGraphEdgeStrength::Strong,
                sourcePackage: LayoutBuilderServiceProvider::$packageName,
                siteId: is_numeric($model->site_id) ? (int) $model->site_id : null,
            ))->all());
    }
}
