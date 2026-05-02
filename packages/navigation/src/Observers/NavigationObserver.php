<?php

declare(strict_types=1);

namespace Capell\Navigation\Observers;

use Capell\Core\Actions\GenerateUniqueKeyAction;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Support\CapellCoreHelper;
use Capell\Frontend\Enums\CacheEnum as FrontendCacheEnum;
use Capell\Navigation\Enums\NavigationCacheEnum;
use Capell\Navigation\Models\Navigation;

class NavigationObserver
{
    public function saving(Navigation $navigation): void
    {
        if ($navigation->key === null || $navigation->key === '') {
            $navigation->key = GenerateUniqueKeyAction::run($navigation);
        }
    }

    public function saved(Navigation $navigation): void
    {
        $this->clearCache($navigation);
    }

    public function deleted(Navigation $navigation): void
    {
        $this->clearCache($navigation);
    }

    public function restored(Navigation $navigation): void
    {
        $this->clearCache($navigation);
    }

    private function clearCache(Navigation $navigation): void
    {
        CapellCoreHelper::flushCache([NavigationCacheEnum::NavigationNames]);

        CapellCore::removeCacheKey(FrontendCacheEnum::Navigations->value);
        CapellCore::removeCacheKey(FrontendCacheEnum::navigationById((int) $navigation->getKey()));

        $siteIds = collect([$navigation->site_id, $navigation->getOriginal('site_id')])
            ->filter(fn (mixed $siteId): bool => is_numeric($siteId))
            ->map(fn (mixed $siteId): int => (int) $siteId)
            ->unique();

        if ($navigation->site_id === null || $navigation->getOriginal('site_id') === null) {
            $siteIds = $siteIds->merge(Site::query()->pluck('id'));
        }

        foreach ($siteIds->unique() as $siteId) {
            CapellCore::removeCacheKey(FrontendCacheEnum::siteNavigations($siteId));
        }

        $cacheKeys = collect([
            [
                'key' => $navigation->key,
                'site_id' => $navigation->site_id,
                'language_id' => $navigation->language_id,
            ],
            [
                'key' => $navigation->getOriginal('key'),
                'site_id' => $navigation->getOriginal('site_id'),
                'language_id' => $navigation->getOriginal('language_id'),
            ],
        ]);

        foreach ($cacheKeys as $cacheKey) {
            if (! is_string($cacheKey['key'])) {
                continue;
            }

            if (! is_numeric($cacheKey['site_id'])) {
                continue;
            }

            CapellCore::removeCacheKey(FrontendCacheEnum::navigation(
                $cacheKey['key'],
                (int) $cacheKey['site_id'],
                is_numeric($cacheKey['language_id']) ? (int) $cacheKey['language_id'] : null,
            ));
        }
    }
}
