<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Actions;

use Capell\GA4Reports\Data\GA4ReportsDailyMetricData;
use Capell\GA4Reports\Models\GA4ReportsDailyMetric;
use Lorisleiva\Actions\Concerns\AsAction;

final class PersistGA4ReportsDailyMetricAction
{
    use AsAction;

    public function handle(GA4ReportsDailyMetricData $metric): GA4ReportsDailyMetric
    {
        $dailyMetric = GA4ReportsDailyMetric::query()
            ->where('property_id', $metric->propertyId)
            ->whereDate('metric_date', $metric->metricDate->toDateString())
            ->first() ?? new GA4ReportsDailyMetric([
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
