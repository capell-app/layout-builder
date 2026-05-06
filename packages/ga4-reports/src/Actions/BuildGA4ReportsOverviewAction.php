<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Actions;

use Capell\GA4Reports\Data\GA4ReportsOverviewData;
use Capell\GA4Reports\Data\GA4ReportsWindowData;
use Capell\GA4Reports\Models\GA4ReportsDailyMetric;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildGA4ReportsOverviewAction
{
    use AsAction;

    public function handle(?GA4ReportsWindowData $window = null): GA4ReportsOverviewData
    {
        $resolvedWindow = $window ?? BuildGA4ReportsWindowAction::run();

        if ($resolvedWindow === null) {
            return new GA4ReportsOverviewData(0, 0, 0, 0, 0.0, 0.0);
        }

        $query = GA4ReportsDailyMetric::query()
            ->where('property_id', $resolvedWindow->propertyId)
            ->whereDate('metric_date', '>=', $resolvedWindow->startsAt->toDateString())
            ->whereDate('metric_date', '<=', $resolvedWindow->endsAt->toDateString());

        $sessions = (int) (clone $query)->sum('sessions');
        $engagedSessions = (int) (clone $query)->sum('engaged_sessions');

        return new GA4ReportsOverviewData(
            totalUsers: (int) (clone $query)->sum('total_users'),
            sessions: $sessions,
            screenPageViews: (int) (clone $query)->sum('screen_page_views'),
            conversions: (int) (clone $query)->sum('conversions'),
            engagementRate: $sessions === 0 ? 0.0 : round($engagedSessions / $sessions, 4),
            averageSessionDuration: $this->averageSessionDuration($query, $sessions),
        );
    }

    /**
     * @param  Builder<GA4ReportsDailyMetric>  $query
     */
    private function averageSessionDuration(Builder $query, int $sessions): float
    {
        if ($sessions === 0) {
            return 0.0;
        }

        $weightedDuration = (clone $query)->get()
            ->sum(fn (GA4ReportsDailyMetric $metric): float => (float) $metric->average_session_duration * (int) $metric->sessions);

        return round($weightedDuration / $sessions, 2);
    }
}
