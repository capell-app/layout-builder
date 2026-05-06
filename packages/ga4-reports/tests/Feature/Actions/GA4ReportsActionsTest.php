<?php

declare(strict_types=1);

use Capell\GA4Reports\Actions\BuildGA4ReportsOverviewAction;
use Capell\GA4Reports\Actions\BuildGA4ReportsTrendAction;
use Capell\GA4Reports\Actions\BuildTopGA4ReportsPagesAction;
use Capell\GA4Reports\Actions\SyncGA4ReportsMetricsAction;
use Capell\GA4Reports\Contracts\GA4ReportsDataClientInterface;
use Capell\GA4Reports\Data\GA4ReportsDailyMetricData;
use Capell\GA4Reports\Data\GA4ReportsPageMetricData;
use Capell\GA4Reports\Data\GA4ReportsWindowData;
use Capell\GA4Reports\Models\GA4ReportsDailyMetric;
use Capell\GA4Reports\Models\GA4ReportsPageMetric;
use Capell\GA4Reports\Models\GA4ReportsSyncRun;
use Capell\GA4Reports\Settings\GA4ReportsSettings;
use Capell\GA4Reports\Tests\Fakes\FakeGA4ReportsDataClient;
use Capell\GA4Reports\Tests\GA4ReportsTestCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

uses(GA4ReportsTestCase::class);

function configureGA4ReportsSettings(): void
{
    $settings = new GA4ReportsSettings;
    $settings->enabled = true;
    $settings->property_id = '123456789';
    $settings->credentials_path = '/tmp/ga4-reports.json';
    $settings->sync_days = 2;
    $settings->route_slug = 'ga4-reports';

    app()->instance(GA4ReportsSettings::class, $settings);
}

it('syncs GA4 metrics idempotently into local reporting tables', function (): void {
    Date::setTestNow(Date::create(2026, 5, 5, 12, 0, 0));
    configureGA4ReportsSettings();

    app()->instance(GA4ReportsDataClientInterface::class, new FakeGA4ReportsDataClient(
        configured: true,
        dailyMetrics: [
            new GA4ReportsDailyMetricData(
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
            new GA4ReportsPageMetricData(
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

    $firstResult = SyncGA4ReportsMetricsAction::run();
    $secondResult = SyncGA4ReportsMetricsAction::run();

    Date::setTestNow();

    expect($firstResult->synced)->toBeTrue()
        ->and($secondResult->synced)->toBeTrue()
        ->and(GA4ReportsDailyMetric::query()->count())->toBe(1)
        ->and(GA4ReportsPageMetric::query()->count())->toBe(1)
        ->and(GA4ReportsSyncRun::query()->where('status', 'succeeded')->count())->toBe(2)
        ->and(GA4ReportsDailyMetric::query()->first()?->screen_page_views)->toBe(30)
        ->and(GA4ReportsPageMetric::query()->first()?->page_path)->toBe('/about');
});

it('builds overview trend and top page data from local tables only', function (): void {
    configureGA4ReportsSettings();

    GA4ReportsDailyMetric::query()->create([
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
    GA4ReportsDailyMetric::query()->create([
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
    GA4ReportsPageMetric::query()->create([
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

    $window = new GA4ReportsWindowData(
        startsAt: CarbonImmutable::create(2026, 5, 3),
        endsAt: CarbonImmutable::create(2026, 5, 4),
        propertyId: '123456789',
    );

    $overview = BuildGA4ReportsOverviewAction::run($window);
    $trend = BuildGA4ReportsTrendAction::run($window);
    $topPages = BuildTopGA4ReportsPagesAction::run($window);

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
    app()->instance(GA4ReportsDataClientInterface::class, new FakeGA4ReportsDataClient(configured: false));

    $result = SyncGA4ReportsMetricsAction::run();

    expect($result->synced)->toBeFalse()
        ->and(GA4ReportsSyncRun::query()->count())->toBe(0);
});

it('records a failed sync run when GA4 fetches fail', function (): void {
    configureGA4ReportsSettings();

    app()->instance(GA4ReportsDataClientInterface::class, new FakeGA4ReportsDataClient(
        configured: true,
        shouldFail: true,
    ));

    $result = SyncGA4ReportsMetricsAction::run();
    $syncRun = GA4ReportsSyncRun::query()->first();

    expect($result->synced)->toBeFalse()
        ->and($syncRun)->toBeInstanceOf(GA4ReportsSyncRun::class)
        ->and($syncRun?->status)->toBe('failed')
        ->and($syncRun?->error_message)->toBe('GA4 client failed.')
        ->and($syncRun?->finished_at)->not->toBeNull();
});
