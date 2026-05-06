<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Actions;

use Capell\Core\Actions\GetUrlCachePathAction;
use Capell\Core\Actions\LoadSiteDomainFromUrlAction;
use Capell\Core\Actions\VisitUrlAction;
use Capell\Core\Enums\CacheEnum;
use Capell\Core\Support\Cache\PageCacheService;
use Capell\Frontend\Support\Loader\SiteLoader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Lorisleiva\Actions\Concerns\AsObject;
use ReflectionClass;

class ClearAffectedCachedUrlsAction
{
    use AsObject;

    /**
     * @param  list<string>  $urls
     */
    public function handle(Model $model, array $urls, string $currentUrl): int
    {
        $cleared = 0;

        foreach ($urls as $url) {
            $siteDomainAndPath = LoadSiteDomainFromUrlAction::run($url, sites: SiteLoader::getSites());

            if (! is_array($siteDomainAndPath) || ! isset($siteDomainAndPath[0], $siteDomainAndPath[1])) {
                continue;
            }

            $file = GetUrlCachePathAction::run((string) $siteDomainAndPath[1], $siteDomainAndPath[0]);

            if (in_array($file, [null, '', '0'], true)) {
                continue;
            }

            resolve(PageCacheService::class)->delete(str_replace(['../', '..\\'], '', $file));
            $this->removeModelFromUrlIndex($model, $url);
            $cleared++;

            if (config('capell-admin.auto_refresh_cache') === true || $url === $currentUrl) {
                VisitUrlAction::dispatch($url, (int) $model->getKey());
            }
        }

        return $cleared;
    }

    private function removeModelFromUrlIndex(Model $model, string $url): void
    {
        $cacheKey = CacheEnum::modelUrlCacheKey();
        $cacheIndex = Cache::get($cacheKey);

        if (! is_array($cacheIndex) || ! isset($cacheIndex[$url]) || ! is_array($cacheIndex[$url])) {
            return;
        }

        $modelCacheKey = (new ReflectionClass($model))->getShortName();
        unset($cacheIndex[$url][$modelCacheKey]);

        if ($cacheIndex[$url] === []) {
            unset($cacheIndex[$url]);
        }

        if ($cacheIndex === []) {
            Cache::forget($cacheKey);

            return;
        }

        Cache::put($cacheKey, $cacheIndex);
    }
}
