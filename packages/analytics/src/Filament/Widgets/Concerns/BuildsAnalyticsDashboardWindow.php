<?php

declare(strict_types=1);

namespace Capell\Analytics\Filament\Widgets\Concerns;

use Capell\Admin\Filament\Concerns\HasDashboardDateRange;
use Capell\Analytics\Data\AnalyticsWindowData;

trait BuildsAnalyticsDashboardWindow
{
    use HasDashboardDateRange;

    private function getAnalyticsWindow(): AnalyticsWindowData
    {
        [$rangeStart, $rangeEnd] = $this->getDashboardDateRange();

        return new AnalyticsWindowData(
            startsAt: $rangeStart,
            endsAt: $rangeEnd,
        );
    }
}
