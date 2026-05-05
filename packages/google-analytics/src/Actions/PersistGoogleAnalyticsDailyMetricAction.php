<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Actions;

use Capell\GoogleAnalytics\Data\GoogleAnalyticsDailyMetricData;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsDailyMetric;
use Lorisleiva\Actions\Concerns\AsAction;

final class PersistGoogleAnalyticsDailyMetricAction
{
    use AsAction;

    public function handle(GoogleAnalyticsDailyMetricData $metric): GoogleAnalyticsDailyMetric
    {
        $dailyMetric = GoogleAnalyticsDailyMetric::query()
            ->where('property_id', $metric->propertyId)
            ->whereDate('metric_date', $metric->metricDate->toDateString())
            ->first() ?? new GoogleAnalyticsDailyMetric([
                'property_id' => $metric->propertyId,
                'metric_date' => $metric->metricDate->toDateString(),
            ]);

        $dailyMetric->fill([
            'total_users' => $metric->totalUsers,
            'sessions' => $metric->sessions,
            'screen_page_views' => $metric->screenPageViews,
            'engaged_sessions' => $metric->engagedSessions,
            'engagement_rate' => $metric->engagementRate,
            'average_session_duration' => $metric->averageSessionDuration,
            'event_count' => $metric->eventCount,
            'conversions' => $metric->conversions,
        ]);
        $dailyMetric->save();

        return $dailyMetric;
    }
}
