<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Actions\Dashboard;

use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Support\Cache\PageCacheService;
use Capell\Diagnostics\Data\Dashboard\CacheHealthData;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static CacheHealthData run(Site $site)
 */
final class BuildCacheHealthAction
{
    use AsAction;

    public function handle(Site $site): CacheHealthData
    {
        /** @var class-string<PageUrl> $model */
        $model = PageUrl::class;

        $service = resolve(PageCacheService::class);

        $query = $model::query()
            ->select(['id', 'site_id', 'language_id', 'url', 'type', 'status', 'updated_at'])
            ->with(['siteDomain'])
            ->where('site_id', $site->id)
            ->enabled()
            ->where(function (Builder $query): void {
                $query->whereNull('type')
                    ->orWhere('type', '!=', UrlTypeEnum::Redirect->value);
            });

        $totalEnabledUrls = (clone $query)->count();

        $cachedCount = 0;
        $staleCount = 0;
        $missingCount = 0;
        $newestTimestamp = null;

        foreach ($query->lazyById(500) as $pageUrl) {
            $cacheFile = $pageUrl->page_cache_file;

            if ($cacheFile === null || ! $service->exists($cacheFile)) {
                $missingCount++;

                continue;
            }

            $lastModified = $service->lastModified($cacheFile);

            if ($lastModified !== null && ($newestTimestamp === null || $lastModified > $newestTimestamp)) {
                $newestTimestamp = $lastModified;
            }

            $pageUpdatedAt = $pageUrl->updated_at;

            if ($lastModified !== null && $pageUpdatedAt !== null && $lastModified < $pageUpdatedAt->getTimestamp()) {
                $staleCount++;
            } else {
                $cachedCount++;
            }
        }

        $lastWarmedAt = $newestTimestamp !== null
            ? CarbonImmutable::createFromTimestamp($newestTimestamp)->toIso8601String()
            : null;

        return new CacheHealthData(
            totalEnabledUrls: $totalEnabledUrls,
            cachedCount: $cachedCount,
            staleCount: $staleCount,
            missingCount: $missingCount,
            lastWarmedAt: $lastWarmedAt,
            siteId: $site->id,
            siteName: $site->name,
        );
    }
}
