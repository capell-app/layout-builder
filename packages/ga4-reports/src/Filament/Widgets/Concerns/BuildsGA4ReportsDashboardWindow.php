<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Filament\Widgets\Concerns;

use Capell\Admin\Filament\Concerns\HasDashboardDateRange;
use Capell\GA4Reports\Actions\BuildGA4ReportsWindowAction;
use Capell\GA4Reports\Data\GA4ReportsWindowData;

trait BuildsGA4ReportsDashboardWindow
{
    use HasDashboardDateRange;

    private function getGA4ReportsWindow(): ?GA4ReportsWindowData
    {
        [$rangeStart, $rangeEnd] = $this->getDashboardDateRange();

        return BuildGA4ReportsWindowAction::run($rangeStart, $rangeEnd);
    }
}
