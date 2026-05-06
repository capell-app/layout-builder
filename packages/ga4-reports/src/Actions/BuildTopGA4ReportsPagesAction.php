<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Actions;

use Capell\GA4Reports\Data\GA4ReportsTopPageData;
use Capell\GA4Reports\Data\GA4ReportsWindowData;
use Capell\GA4Reports\Models\GA4ReportsPageMetric;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildTopGA4ReportsPagesAction
{
    use AsAction;

    /**
     * @return list<GA4ReportsTopPageData>
     */
    public function handle(?GA4ReportsWindowData $window = null, int $limit = 10): array
    {
        $resolvedWindow = $window ?? BuildGA4ReportsWindowAction::run();

        if ($resolvedWindow === null) {
            return [];
        }

        return GA4ReportsPageMetric::query()
            ->select([
                'page_path',
                DB::raw('MAX(page_title) as page_title'),
                DB::raw('SUM(screen_page_views) as screen_page_views'),
                DB::raw('SUM(sessions) as sessions'),
                DB::raw('SUM(total_users) as total_users'),
                DB::raw('SUM(conversions) as conversions'),
            ])
            ->where('property_id', $resolvedWindow->propertyId)
            ->whereDate('metric_date', '>=', $resolvedWindow->startsAt->toDateString())
            ->whereDate('metric_date', '<=', $resolvedWindow->endsAt->toDateString())
            ->groupBy('page_path')
            ->orderByDesc('screen_page_views')
            ->limit($limit)
            ->get()
            ->map(fn (GA4ReportsPageMetric $metric): GA4ReportsTopPageData => new GA4ReportsTopPageData(
                pagePath: (string) $metric->page_path,
                pageTitle: is_string($metric->page_title) ? $metric->page_title : null,
                screenPageViews: (int) $metric->screen_page_views,
                sessions: (int) $metric->sessions,
                totalUsers: (int) $metric->total_users,
                conversions: (int) $metric->conversions,
            ))
            ->all();
    }
}
