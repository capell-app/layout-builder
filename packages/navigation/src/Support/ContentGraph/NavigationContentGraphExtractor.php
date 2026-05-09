<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\ContentGraph;

use Capell\Core\Contracts\ContentGraph\ContentGraphExtractor;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeCollectionData;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeData;
use Capell\Core\Data\ContentGraph\ContentGraphNodeData;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeKind;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeStrength;
use Capell\Navigation\Actions\ResolveNavigationItemModelsAction;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\DataCollection;

final class NavigationContentGraphExtractor implements ContentGraphExtractor
{
    public static function sourceModel(): string
    {
        return Navigation::class;
    }

    public function extract(Model $model): ContentGraphEdgeCollectionData
    {
        /** @var Navigation $navigation */
        $navigation = $model;
        $source = ContentGraphNodeData::fromModel($navigation);
        $items = $navigation->items instanceof DataCollection
            ? $navigation->items->toArray()
            : ($navigation->items ?? []);

        $edges = ResolveNavigationItemModelsAction::run($items)
            ->map(fn (Model $page): ContentGraphEdgeData => new ContentGraphEdgeData(
                source: $source,
                target: ContentGraphNodeData::fromModelIdentity($page::class, $page->getKey()),
                kind: ContentGraphEdgeKind::LinksToPage,
                strength: ContentGraphEdgeStrength::Strong,
                sourcePackage: 'capell-app/navigation',
                siteId: is_numeric($navigation->site_id) ? (int) $navigation->site_id : null,
                languageId: is_numeric($navigation->language_id) ? (int) $navigation->language_id : null,
            ))
            ->all();

        return ContentGraphEdgeCollectionData::make($edges);
    }
}
