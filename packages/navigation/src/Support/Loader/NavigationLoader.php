<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Loader;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Frontend\Enums\CacheEnum;
use Capell\Frontend\Support\ModelServing\RetrievedModelStore;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class NavigationLoader
{
    public static function getNavigation(NavigationHandle|string $key, Site $site, ?Language $language = null, bool $siteOnlyFallback = true): ?Navigation
    {
        $navigationKey = $key instanceof NavigationHandle ? $key->value : $key;
        $navigations = $site->navigations;

        $navigation = null;

        if ($language instanceof Language) {
            $navigation = $navigations->where('key', $navigationKey)
                ->where('language_id', $language->id)
                ->first();
        }

        if ($siteOnlyFallback && $navigation === null) {
            $navigations = CapellCore::rememberCache(
                CacheEnum::siteNavigations((int) $site->getKey()),
                function () use ($site): Collection {
                    /** @var class-string<Navigation> $model */
                    $model = Navigation::class;

                    return $model::query()
                        ->where(function (Builder $query) use ($site): void {
                            $query->where('site_id', $site->getKey())
                                ->orWhereNull('site_id');
                        })
                        ->get();
                },
            );

            $navigation = $navigations
                ->where('key', $navigationKey)
                ->filter(
                    fn (Navigation $navigation): bool => $navigation->language_id === null
                        || ($language instanceof Language && $navigation->language_id === $language->getKey()),
                )
                ->sort(
                    fn (Navigation $firstNavigation, Navigation $secondNavigation): int => [
                        $firstNavigation->site_id === $site->getKey() ? 0 : 1,
                        $language instanceof Language && $firstNavigation->language_id === $language->getKey() ? 0 : 1,
                    ] <=> [
                        $secondNavigation->site_id === $site->getKey() ? 0 : 1,
                        $language instanceof Language && $secondNavigation->language_id === $language->getKey() ? 0 : 1,
                    ],
                )
                ->first();
        }

        if ($navigation) {
            resolve(RetrievedModelStore::class)->track($navigation);
        }

        return $navigation;
    }

    public static function getNavigationById(int $id): ?Navigation
    {
        $key = CacheEnum::navigationById($id);

        $fromCache = true;

        $navigation = CapellCore::rememberCache($key, function () use ($id, &$fromCache): ?Navigation {
            $fromCache = false;

            /** @var class-string<Navigation> $model */
            $model = Navigation::class;

            return $model::query()->find($id);
        });

        if ($fromCache && $navigation !== null) {
            resolve(RetrievedModelStore::class)->track($navigation);
        }

        return $navigation;
    }
}
