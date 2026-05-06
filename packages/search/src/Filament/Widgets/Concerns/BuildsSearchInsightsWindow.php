<?php

declare(strict_types=1);

namespace Capell\Search\Filament\Widgets\Concerns;

use Capell\Admin\Filament\Concerns\HasDashboardDateRange;
use Capell\Search\Data\SearchInsightsWindowData;

trait BuildsSearchInsightsWindow
{
    use HasDashboardDateRange;

    protected function getInsightsWindow(): SearchInsightsWindowData
    {
        $days = config('capell-search.dashboard.default_days', 30);
        $fallbackDays = is_int($days) ? max(1, $days) : 30;
        [$rangeStart, $rangeEnd] = $this->getDashboardDateRange($fallbackDays);

        return new SearchInsightsWindowData(
            start: $rangeStart,
            end: $rangeEnd,
        );
    }
}
