<?php

declare(strict_types=1);

namespace Capell\Navigation\Adapters;

use Capell\Core\Contracts\Navigation\NavigationNamesResolver;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Builder;

class NavigationNamesResolverAdapter implements NavigationNamesResolver
{
    /**
     * @param  array<int, int>  $languageIds
     * @return array<int, string>
     */
    public function resolve(?int $siteId, array $languageIds): array
    {
        return Navigation::query()
            ->select(['id', 'name'])
            ->where(function (Builder $query) use ($siteId): void {
                $query->where('site_id', $siteId)->orWhereNull('site_id');
            })
            ->where(function (Builder $query) use ($languageIds): void {
                $query->whereIn('language_id', $languageIds)->orWhereNull('language_id');
            })
            ->pluck('name', 'id')
            ->toArray();
    }
}
