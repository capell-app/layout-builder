<?php

declare(strict_types=1);

namespace Capell\Insights\Filament\Widgets\Concerns;

use Capell\Admin\Filament\Concerns\HasDashboardDateRange;
use Capell\Insights\Data\InsightsWindowData;

trait BuildsInsightsDashboardWindow
{
    use HasDashboardDateRange;

    private function getInsightsWindow(): InsightsWindowData
    {
        [$rangeStart, $rangeEnd] = $this->getDashboardDateRange();

        return new InsightsWindowData(
            startsAt: $rangeStart,
            endsAt: $rangeEnd,
        );
    }
}
