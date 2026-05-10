<?php

declare(strict_types=1);

namespace Capell\HtmlCache\Actions;

use Capell\Admin\Support\SiteScope;
use Capell\HtmlCache\Data\CacheMap\CacheMapResourceSummaryData;
use Capell\HtmlCache\Models\CachedModelUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array run(string $modelType, ?int $siteId = null, ?string $search = null, int $limit = 5)
 */
final class ListCacheMapResourceOptionsAction
{
    use AsAction;

    /**
     * @return list<CacheMapResourceSummaryData>
     */
    public function handle(string $modelType, ?int $siteId = null, ?string $search = null, int $limit = 5): array
    {
        /** @var Builder<CachedModelUrl> $query */
        $query = SiteScope::applyForCurrentActor(CachedModelUrl::query(), denyWhenMissingActor: true)
            ->where('cacheable_type', $modelType);

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        $search = is_string($search) ? trim($search) : '';

        /** @var EloquentCollection<int, CachedModelUrl> $rows */
        $rows = $query
            ->select('cacheable_type', 'cacheable_id')
            ->selectRaw('COUNT(*) as dependency_count')
            ->selectRaw('COUNT(DISTINCT url_hash) as url_count')
            ->groupBy('cacheable_type', 'cacheable_id')
            ->orderByDesc('url_count')
            ->orderByDesc('dependency_count')
            ->orderBy('cacheable_id')
            ->get();

        return $rows
            ->map(fn (CachedModelUrl $row): CacheMapResourceSummaryData => new CacheMapResourceSummaryData(
                key: $this->resourceKey($row->cacheable_type, $row->cacheable_id),
                modelType: $row->cacheable_type,
                modelLabel: class_basename($row->cacheable_type),
                resourceId: $row->cacheable_id,
                label: $this->resourceLabel($row->cacheable_type, $row->cacheable_id),
                dependencyCount: (int) $row->getAttribute('dependency_count'),
                urlCount: (int) $row->getAttribute('url_count'),
            ))
            ->filter(fn (CacheMapResourceSummaryData $resource): bool => $this->matchesSearch($resource, $search))
            ->take($limit)
            ->values()
            ->all();
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

    private function matchesSearch(CacheMapResourceSummaryData $resource, string $search): bool
    {
        if ($search === '') {
            return true;
        }

        return str_contains(strtolower($resource->label), strtolower($search))
            || str_contains((string) $resource->resourceId, $search);
    }
}
