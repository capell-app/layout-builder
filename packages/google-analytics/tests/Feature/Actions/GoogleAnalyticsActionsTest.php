<?php

declare(strict_types=1);

use Capell\GoogleAnalytics\Actions\BuildGoogleAnalyticsOverviewAction;
use Capell\GoogleAnalytics\Actions\BuildGoogleAnalyticsTrendAction;
use Capell\GoogleAnalytics\Actions\BuildTopGoogleAnalyticsPagesAction;
use Capell\GoogleAnalytics\Actions\SyncGoogleAnalyticsMetricsAction;
use Capell\GoogleAnalytics\Contracts\GoogleAnalyticsDataClientInterface;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsDailyMetricData;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsPageMetricData;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsWindowData;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsDailyMetric;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsPageMetric;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsSyncRun;
use Capell\GoogleAnalytics\Settings\GoogleAnalyticsSettings;
use Capell\GoogleAnalytics\Tests\Fakes\FakeGoogleAnalyticsDataClient;
use Capell\GoogleAnalytics\Tests\GoogleAnalyticsTestCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

uses(GoogleAnalyticsTestCase::class);

function configureGoogleAnalyticsSettings(): void
{
    $settings = new GoogleAnalyticsSettings;
    $settings->enabled = true;
    $settings->property_id = '123456789';
    $settings->credentials_path = '/tmp/google-analytics.json';
    $settings->sync_days = 2;
    $settings->route_slug = 'google-analytics';

    app()->instance(GoogleAnalyticsSettings::class, $settings);
}

it('syncs GA4 metrics idempotently into local reporting tables', function (): void {
    Date::setTestNow(Date::create(2026, 5, 5, 12, 0, 0));
    configureGoogleAnalyticsSettings();

    app()->instance(GoogleAnalyticsDataClientInterface::class, new FakeGoogleAnalyticsDataClient(
        configured: true,
        dailyMetrics: [
            new GoogleAnalyticsDailyMetricData(
                propertyId: '123456789',
                metricDate: CarbonImmutable::create(2026, 5, 4),
                totalUsers: 10,
                sessions: 20,
                screenPageViews: 30,
                engagedSessions: 15,
                engagementRate: 0.75,
                averageSessionDuration: 42.5,
                eventCount: 80,
                conversions: 4,
            ),
        ],
        pageMetrics: [
            new GoogleAnalyticsPageMetricData(
                propertyId: '123456789',
                metricDate: CarbonImmutable::create(2026, 5, 4),
                pagePath: '/about',
                pageTitle: 'About',
                totalUsers: 8,
                sessions: 12,
                screenPageViews: 22,
                eventCount: 40,
                conversions: 2,
            ),
        ],
    ));

    $firstResult = SyncGoogleAnalyticsMetricsAction::run();
    $secondResult = SyncGoogleAnalyticsMetricsAction::run();

    Date::setTestNow();

    expect($firstResult->synced)->toBeTrue()
        ->and($secondResult->synced)->toBeTrue()
        ->and(GoogleAnalyticsDailyMetric::query()->count())->toBe(1)
        ->and(GoogleAnalyticsPageMetric::query()->count())->toBe(1)
        ->and(GoogleAnalyticsSyncRun::query()->where('status', 'succeeded')->count())->toBe(2)
        ->and(GoogleAnalyticsDailyMetric::query()->first()?->screen_page_views)->toBe(30)
        ->and(GoogleAnalyticsPageMetric::query()->first()?->page_path)->toBe('/about');
});

it('builds overview trend and top page data from local tables only', function (): void {
    configureGoogleAnalyticsSettings();

    GoogleAnalyticsDailyMetric::query()->create([
        'property_id' => '123456789',
        'metric_date' => '2026-05-03',
        'total_users' => 5,
        'sessions' => 10,
        'screen_page_views' => 20,
        'engaged_sessions' => 6,
        'engagement_rate' => 0.6,
        'average_session_duration' => 20,
        'event_count' => 40,
        'conversions' => 1,
    ]);
    GoogleAnalyticsDailyMetric::query()->create([
        'property_id' => '123456789',
        'metric_date' => '2026-05-04',
        'total_users' => 8,
        'sessions' => 20,
        'screen_page_views' => 35,
        'engaged_sessions' => 14,
        'engagement_rate' => 0.7,
        'average_session_duration' => 50,
        'event_count' => 60,
        'conversions' => 3,
    ]);
    GoogleAnalyticsPageMetric::query()->create([
        'property_id' => '123456789',
        'metric_date' => '2026-05-04',
        'page_path' => '/about',
        'page_title' => 'About',
        'total_users' => 8,
        'sessions' => 20,
        'screen_page_views' => 35,
        'event_count' => 60,
        'conversions' => 3,
    ]);

    $window = new GoogleAnalyticsWindowData(
        startsAt: CarbonImmutable::create(2026, 5, 3),
        endsAt: CarbonImmutable::create(2026, 5, 4),
        propertyId: '123456789',
    );

    $overview = BuildGoogleAnalyticsOverviewAction::run($window);
    $trend = BuildGoogleAnalyticsTrendAction::run($window);
    $topPages = BuildTopGoogleAnalyticsPagesAction::run($window);

    expect($overview->screenPageViews)->toBe(55)
        ->and($overview->sessions)->toBe(30)
        ->and($overview->totalUsers)->toBe(13)
        ->and($overview->conversions)->toBe(4)
        ->and($overview->engagementRate)->toBe(0.6667)
        ->and($overview->averageSessionDuration)->toBe(40.0)
        ->and($trend)->toHaveCount(2)
        ->and($trend[0]->screenPageViews)->toBe(20)
        ->and($topPages)->toHaveCount(1)
        ->and($topPages[0]->pagePath)->toBe('/about')
        ->and($topPages[0]->screenPageViews)->toBe(35);
});

it('returns an empty sync result when GA4 is not configured', function (): void {
    app()->instance(GoogleAnalyticsDataClientInterface::class, new FakeGoogleAnalyticsDataClient(configured: false));

    $result = SyncGoogleAnalyticsMetricsAction::run();

    expect($result->synced)->toBeFalse()
        ->and(GoogleAnalyticsSyncRun::query()->count())->toBe(0);
});
