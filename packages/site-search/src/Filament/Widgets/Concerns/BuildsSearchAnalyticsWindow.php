<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Filament\Widgets\Concerns;

use Capell\SiteSearch\Data\SearchAnalyticsWindowData;

trait BuildsSearchAnalyticsWindow
{
    protected function getAnalyticsWindow(): SearchAnalyticsWindowData
    {
        $days = config('capell-site-search.dashboard.default_days', 30);

        return new SearchAnalyticsWindowData(
            start: now()->subDays(max(1, $days))->toImmutable(),
            end: now()->toImmutable(),
        );
    }
}
