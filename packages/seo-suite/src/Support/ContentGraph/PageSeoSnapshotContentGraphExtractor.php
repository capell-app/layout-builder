<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\ContentGraph;

use Capell\Core\Contracts\ContentGraph\ContentGraphExtractor;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeCollectionData;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeData;
use Capell\Core\Data\ContentGraph\ContentGraphNodeData;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeKind;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeStrength;
use Capell\Core\Models\Page;
use Capell\SeoSuite\Models\PageSeoSnapshot;
use Illuminate\Database\Eloquent\Model;

final class PageSeoSnapshotContentGraphExtractor implements ContentGraphExtractor
{
    public static function sourceModel(): string
    {
        return PageSeoSnapshot::class;
    }

    public function extract(Model $model): ContentGraphEdgeCollectionData
    {
        /** @var PageSeoSnapshot $snapshot */
        $snapshot = $model;

        if (! is_numeric($snapshot->page_id)) {
            return ContentGraphEdgeCollectionData::make();
        }

        return ContentGraphEdgeCollectionData::make([
            new ContentGraphEdgeData(
                source: ContentGraphNodeData::fromModel($snapshot),
                target: ContentGraphNodeData::fromModelIdentity(Page::class, $snapshot->page_id),
                kind: ContentGraphEdgeKind::DescribesPage,
                strength: ContentGraphEdgeStrength::Weak,
                sourcePackage: 'capell-app/seo-suite',
                siteId: is_numeric($snapshot->site_id) ? $snapshot->site_id : null,
                languageId: is_numeric($snapshot->language_id) ? $snapshot->language_id : null,
            ),
        ]);
    }
}
