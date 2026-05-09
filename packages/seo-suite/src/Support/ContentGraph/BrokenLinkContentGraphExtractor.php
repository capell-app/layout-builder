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
use Capell\SeoSuite\Models\BrokenLink;
use Illuminate\Database\Eloquent\Model;

final class BrokenLinkContentGraphExtractor implements ContentGraphExtractor
{
    public static function sourceModel(): string
    {
        return BrokenLink::class;
    }

    public function extract(Model $model): ContentGraphEdgeCollectionData
    {
        /** @var BrokenLink $brokenLink */
        $brokenLink = $model;

        if (! is_numeric($brokenLink->page_id)) {
            return ContentGraphEdgeCollectionData::make();
        }

        return ContentGraphEdgeCollectionData::make([
            new ContentGraphEdgeData(
                source: ContentGraphNodeData::fromModel($brokenLink),
                target: ContentGraphNodeData::fromModelIdentity(Page::class, (int) $brokenLink->page_id),
                kind: ContentGraphEdgeKind::FoundOnPage,
                strength: ContentGraphEdgeStrength::Weak,
                sourcePackage: 'capell-app/seo-suite',
            ),
        ]);
    }
}
