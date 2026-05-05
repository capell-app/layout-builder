<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Actions;

use Capell\GoogleAnalytics\Data\GoogleAnalyticsPageMetricData;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsPageMetric;
use Lorisleiva\Actions\Concerns\AsAction;

final class PersistGoogleAnalyticsPageMetricAction
{
    use AsAction;

    public function handle(GoogleAnalyticsPageMetricData $metric): GoogleAnalyticsPageMetric
    {
        $pageMetric = GoogleAnalyticsPageMetric::query()
            ->where('property_id', $metric->propertyId)
            ->whereDate('metric_date', $metric->metricDate->toDateString())
            ->where('page_path', $metric->pagePath)
            ->first() ?? new GoogleAnalyticsPageMetric([
                'property_id' => $metric->propertyId,
                'metric_date' => $metric->metricDate->toDateString(),
                'page_path' => $metric->pagePath,
            ]);

        $pageMetric->fill([
            'page_title' => $metric->pageTitle,
            'total_users' => $metric->totalUsers,
            'sessions' => $metric->sessions,
            'screen_page_views' => $metric->screenPageViews,
            'event_count' => $metric->eventCount,
            'conversions' => $metric->conversions,
        ]);
        $pageMetric->save();

        return $pageMetric;
    }
}
