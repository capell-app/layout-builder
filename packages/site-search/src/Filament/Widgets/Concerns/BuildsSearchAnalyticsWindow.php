<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Filament\Widgets\Concerns;

use Capell\Admin\Filament\Concerns\HasDashboardDateRange;
use Capell\SiteSearch\Data\SearchAnalyticsWindowData;

trait BuildsSearchAnalyticsWindow
{
    use HasDashboardDateRange;

    protected function getAnalyticsWindow(): SearchAnalyticsWindowData
    {
        $days = config('capell-site-search.dashboard.default_days', 30);
        $fallbackDays = is_int($days) ? max(1, $days) : 30;
        [$rangeStart, $rangeEnd] = $this->getDashboardDateRange($fallbackDays);

        return new SearchAnalyticsWindowData(
            start: $rangeStart,
            end: $rangeEnd,
        );
    }
}
