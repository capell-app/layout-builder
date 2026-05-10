<?php

declare(strict_types=1);

namespace Capell\HtmlCache\Actions;

use Capell\Admin\Support\SiteScope;
use Capell\HtmlCache\Data\CacheMap\CacheMapModelSummaryData;
use Capell\HtmlCache\Data\CacheMap\CacheMapOverviewData;
use Capell\HtmlCache\Data\CacheMap\CacheMapResourceSummaryData;
use Capell\HtmlCache\Models\CachedModelUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static CacheMapOverviewData run(?int $siteId = null)
 */
final class BuildCacheMapOverviewAction
{
    use AsAction;

    public function handle(?int $siteId = null): CacheMapOverviewData
    {
        $baseQuery = $this->baseQuery($siteId);

        return new CacheMapOverviewData(
            totalUrls: (clone $baseQuery)->distinct('url_hash')->count('url_hash'),
            totalDependencies: (clone $baseQuery)->count(),
            modelSummaries: $this->modelSummaries($baseQuery),
            topResources: $this->topResources($baseQuery),
        );
    }

    /**
     * @param  Builder<CachedModelUrl>  $baseQuery
     * @return list<CacheMapModelSummaryData>
     */
    private function modelSummaries(Builder $baseQuery): array
    {
        return (clone $baseQuery)
            ->select('cacheable_type')
            ->selectRaw('COUNT(*) as dependency_count')
            ->selectRaw('COUNT(DISTINCT url_hash) as url_count')
            ->groupBy('cacheable_type')
            ->orderByDesc('url_count')
            ->orderBy('cacheable_type')
            ->get()
            ->map(fn (CachedModelUrl $row): CacheMapModelSummaryData => new CacheMapModelSummaryData(
                modelType: $row->cacheable_type,
                label: class_basename($row->cacheable_type),
                dependencyCount: (int) $row->getAttribute('dependency_count'),
                urlCount: (int) $row->getAttribute('url_count'),
            ))
            ->values()
            ->all();
    }

    /**
     * @param  Builder<CachedModelUrl>  $baseQuery
     * @return list<CacheMapResourceSummaryData>
     */
    private function topResources(Builder $baseQuery): array
    {
        /** @var EloquentCollection<int, CachedModelUrl> $rows */
        $rows = (clone $baseQuery)
            ->select('cacheable_type', 'cacheable_id')
            ->selectRaw('MIN(id) as sample_id')
            ->selectRaw('COUNT(*) as dependency_count')
            ->selectRaw('COUNT(DISTINCT url_hash) as url_count')
            ->groupBy('cacheable_type', 'cacheable_id')
            ->orderByDesc('url_count')
            ->orderByDesc('dependency_count')
            ->orderBy('cacheable_type')
            ->orderBy('cacheable_id')
            ->limit(8)
            ->get();

        return $rows
            ->map(fn (CachedModelUrl $row): CacheMapResourceSummaryData => $this->resourceSummary($row))
            ->values()
            ->all();
    }

    private function resourceSummary(CachedModelUrl $row): CacheMapResourceSummaryData
    {
        $modelType = $row->cacheable_type;
        $resourceId = $row->cacheable_id;

        return new CacheMapResourceSummaryData(
            key: $this->resourceKey($modelType, $resourceId),
            modelType: $modelType,
            modelLabel: class_basename($modelType),
            resourceId: $resourceId,
            label: $this->resourceLabel($modelType, $resourceId),
            dependencyCount: (int) $row->getAttribute('dependency_count'),
            urlCount: (int) $row->getAttribute('url_count'),
        );
    }

    private function resourceLabel(string $modelType, int $resourceId): string
    {
        $record = CachedModelUrl::query()
            ->with('cacheable')
            ->where('cacheable_type', $modelType)
            ->where('cacheable_id', $resourceId)
            ->first();

        if ($record instanceof CachedModelUrl) {
            return $record->cacheableLabel();
        }

        return class_basename($modelType) . ' #' . $resourceId;
    }

    private function resourceKey(string $modelType, int $resourceId): string
    {
        return base64_encode($modelType . '|' . $resourceId);
    }

    private function baseQuery(?int $siteId): Builder
    {
        /** @var Builder<CachedModelUrl> $query */
        $query = SiteScope::applyForCurrentActor(CachedModelUrl::query(), denyWhenMissingActor: true);

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        return $query;
    }
}
