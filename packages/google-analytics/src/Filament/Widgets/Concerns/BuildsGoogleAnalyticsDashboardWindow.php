<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Filament\Widgets\Concerns;

use Capell\Admin\Filament\Concerns\HasDashboardDateRange;
use Capell\GoogleAnalytics\Actions\BuildGoogleAnalyticsWindowAction;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsWindowData;

trait BuildsGoogleAnalyticsDashboardWindow
{
    use HasDashboardDateRange;

    private function getGoogleAnalyticsWindow(): ?GoogleAnalyticsWindowData
    {
        [$rangeStart, $rangeEnd] = $this->getDashboardDateRange();

        return BuildGoogleAnalyticsWindowAction::run($rangeStart, $rangeEnd);
    }
}
