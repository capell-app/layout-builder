<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Actions;

use Capell\GA4Reports\Data\GA4ReportsTrendPointData;
use Capell\GA4Reports\Data\GA4ReportsWindowData;
use Capell\GA4Reports\Models\GA4ReportsDailyMetric;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildGA4ReportsTrendAction
{
    use AsAction;

    /**
     * @return list<GA4ReportsTrendPointData>
     */
    public function handle(?GA4ReportsWindowData $window = null): array
    {
        $resolvedWindow = $window ?? BuildGA4ReportsWindowAction::run();

        if ($resolvedWindow === null) {
            return [];
        }

        return GA4ReportsDailyMetric::query()
            ->where('property_id', $resolvedWindow->propertyId)
            ->whereDate('metric_date', '>=', $resolvedWindow->startsAt->toDateString())
            ->whereDate('metric_date', '<=', $resolvedWindow->endsAt->toDateString())
            ->orderBy('metric_date')
            ->get()
            ->map(fn (GA4ReportsDailyMetric $metric): GA4ReportsTrendPointData => new GA4ReportsTrendPointData(
                label: $metric->metric_date->format('j M'),
                screenPageViews: (int) $metric->screen_page_views,
                sessions: (int) $metric->sessions,
                totalUsers: (int) $metric->total_users,
            ))
            ->all();
    }
}
