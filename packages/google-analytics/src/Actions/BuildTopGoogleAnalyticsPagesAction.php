<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Actions;

use Capell\GoogleAnalytics\Data\GoogleAnalyticsTopPageData;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsWindowData;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsPageMetric;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildTopGoogleAnalyticsPagesAction
{
    use AsAction;

    /**
     * @return list<GoogleAnalyticsTopPageData>
     */
    public function handle(?GoogleAnalyticsWindowData $window = null, int $limit = 10): array
    {
        $resolvedWindow = $window ?? BuildGoogleAnalyticsWindowAction::run();

        if ($resolvedWindow === null) {
            return [];
        }

        return GoogleAnalyticsPageMetric::query()
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
            ->map(fn (GoogleAnalyticsPageMetric $metric): GoogleAnalyticsTopPageData => new GoogleAnalyticsTopPageData(
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
