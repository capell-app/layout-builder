<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Actions;

use Capell\GoogleAnalytics\Data\GoogleAnalyticsOverviewData;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsWindowData;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsDailyMetric;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildGoogleAnalyticsOverviewAction
{
    use AsAction;

    public function handle(?GoogleAnalyticsWindowData $window = null): GoogleAnalyticsOverviewData
    {
        $resolvedWindow = $window ?? BuildGoogleAnalyticsWindowAction::run();

        if ($resolvedWindow === null) {
            return new GoogleAnalyticsOverviewData(0, 0, 0, 0, 0.0, 0.0);
        }

        $query = GoogleAnalyticsDailyMetric::query()
            ->where('property_id', $resolvedWindow->propertyId)
            ->whereDate('metric_date', '>=', $resolvedWindow->startsAt->toDateString())
            ->whereDate('metric_date', '<=', $resolvedWindow->endsAt->toDateString());

        $sessions = (int) (clone $query)->sum('sessions');
        $engagedSessions = (int) (clone $query)->sum('engaged_sessions');

        return new GoogleAnalyticsOverviewData(
            totalUsers: (int) (clone $query)->sum('total_users'),
            sessions: $sessions,
            screenPageViews: (int) (clone $query)->sum('screen_page_views'),
            conversions: (int) (clone $query)->sum('conversions'),
            engagementRate: $sessions === 0 ? 0.0 : round($engagedSessions / $sessions, 4),
            averageSessionDuration: $this->averageSessionDuration($query, $sessions),
        );
    }

    /**
     * @param  Builder<GoogleAnalyticsDailyMetric>  $query
     */
    private function averageSessionDuration(Builder $query, int $sessions): float
    {
        if ($sessions === 0) {
            return 0.0;
        }

        $weightedDuration = (clone $query)->get()
            ->sum(fn (GoogleAnalyticsDailyMetric $metric): float => (float) $metric->average_session_duration * (int) $metric->sessions);

        return round($weightedDuration / $sessions, 2);
    }
}
