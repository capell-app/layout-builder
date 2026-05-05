<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Actions;

use Capell\GoogleAnalytics\Data\GoogleAnalyticsTrendPointData;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsWindowData;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsDailyMetric;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildGoogleAnalyticsTrendAction
{
    use AsAction;

    /**
     * @return list<GoogleAnalyticsTrendPointData>
     */
    public function handle(?GoogleAnalyticsWindowData $window = null): array
    {
        $resolvedWindow = $window ?? BuildGoogleAnalyticsWindowAction::run();

        if ($resolvedWindow === null) {
            return [];
        }

        return GoogleAnalyticsDailyMetric::query()
            ->where('property_id', $resolvedWindow->propertyId)
            ->whereDate('metric_date', '>=', $resolvedWindow->startsAt->toDateString())
            ->whereDate('metric_date', '<=', $resolvedWindow->endsAt->toDateString())
            ->orderBy('metric_date')
            ->get()
            ->map(fn (GoogleAnalyticsDailyMetric $metric): GoogleAnalyticsTrendPointData => new GoogleAnalyticsTrendPointData(
                label: $metric->metric_date->format('j M'),
                screenPageViews: (int) $metric->screen_page_views,
                sessions: (int) $metric->sessions,
                totalUsers: (int) $metric->total_users,
            ))
            ->all();
    }
}
