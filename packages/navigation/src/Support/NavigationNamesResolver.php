<?php

declare(strict_types=1);

namespace Capell\Navigation\Support;

use Capell\Navigation\Enums\NavigationCacheEnum;
use Capell\Navigation\Models\Navigation;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Builder;

class NavigationNamesResolver
{
    public function __construct(private readonly Repository $cache) {}

    /**
     * Resolve navigation names for given site and languages, with caching.
     *
     * @param  array<int>  $languageIds
     * @return array<int, string>
     */
    public function resolve(int|string|null $siteId, array $languageIds): array
    {
        $siteId = is_string($siteId) ? (int) $siteId : $siteId;
        $cacheKey = NavigationCacheEnum::navigationNamesKey($siteId, $languageIds);

        return $this->cache->remember(
            $cacheKey,
            now()->addDay(),
            function () use ($siteId, $languageIds): array {
                /** @var class-string<Navigation> $model */
                $model = Navigation::class;

                return $model::query()
                    ->select(['id', 'name'])
                    ->where(function (Builder $query) use ($siteId): void {
                        $query->where('site_id', $siteId)->orWhereNull('site_id');
                    })
                    ->where(function (Builder $query) use ($languageIds): void {
                        $query->whereIn('language_id', $languageIds);
                    })
                    ->pluck('name', 'id')
                    ->toArray();
            },
        );
    }
}
