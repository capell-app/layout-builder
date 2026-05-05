<?php

declare(strict_types=1);

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\GoogleAnalytics\Filament\Pages\GoogleAnalyticsPage;
use Capell\GoogleAnalytics\Filament\Settings\Contributors\GoogleAnalyticsDashboardSettingsContributor;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsOverviewStatsWidget;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsSetupStatusWidget;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsTopPagesTableWidget;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsTopPagesWidget;
use Capell\GoogleAnalytics\Filament\Widgets\GoogleAnalyticsTrafficTrendWidget;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsDailyMetric;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsPageMetric;
use Capell\GoogleAnalytics\Settings\GoogleAnalyticsSettings;
use Capell\GoogleAnalytics\Tests\GoogleAnalyticsTestCase;
use Livewire\Livewire;

uses(GoogleAnalyticsTestCase::class);

function configureGoogleAnalyticsFilamentSettings(): void
{
    $settings = new GoogleAnalyticsSettings;
    $settings->enabled = true;
    $settings->property_id = '123456789';
    $settings->credentials_path = '/tmp/google-analytics.json';
    $settings->sync_days = 30;
    $settings->route_slug = 'google-analytics';

    app()->instance(GoogleAnalyticsSettings::class, $settings);
}

it('exposes GA4 dashboard settings keys with translated labels', function (): void {
    $entries = (new GoogleAnalyticsDashboardSettingsContributor)->settingsKeys();

    expect(collect($entries)->pluck('key')->all())->toBe([
        'google_analytics_overview',
        'google_analytics_traffic_trend',
        'google_analytics_top_pages',
        'google_analytics_sync_status',
    ]);

    foreach ($entries as $entry) {
        expect($entry['label'])->toBeString()->not->toBe('')
            ->and(str_contains($entry['label'], 'capell-google-analytics::'))->toBeFalse()
            ->and($entry['group'])->toBeString()->not->toBe('');
    }
});

it('registers GA4 dashboard widgets and settings contributor', function (): void {
    $contributors = collect(app()->tagged(DashboardSettingsContributor::TAG))
        ->map(fn (DashboardSettingsContributor $contributor): string => $contributor::class);

    expect($contributors)->toContain(GoogleAnalyticsDashboardSettingsContributor::class)
        ->and(CapellAdmin::getDashboardWidgets(DashboardEnum::Main))
        ->toContain(GoogleAnalyticsOverviewStatsWidget::class)
        ->toContain(GoogleAnalyticsTrafficTrendWidget::class)
        ->toContain(GoogleAnalyticsTopPagesWidget::class)
        ->toContain(GoogleAnalyticsSetupStatusWidget::class);
});

it('uses translated page labels and configured slug', function (): void {
    configureGoogleAnalyticsFilamentSettings();

    expect(GoogleAnalyticsPage::getSlug())->toBe('google-analytics')
        ->and(GoogleAnalyticsPage::getNavigationLabel())->toBe('Google Analytics')
        ->and(GoogleAnalyticsPage::getNavigationGroup())->toBe('Monitoring')
        ->and((new GoogleAnalyticsPage)->getTitle())->toBe('Google Analytics');
});

it('renders GA4 dashboard widgets with empty and seeded data', function (string $widgetClass): void {
    configureGoogleAnalyticsFilamentSettings();

    GoogleAnalyticsDailyMetric::query()->create([
        'property_id' => '123456789',
        'metric_date' => now()->subDay()->toDateString(),
        'total_users' => 8,
        'sessions' => 12,
        'screen_page_views' => 24,
        'engaged_sessions' => 9,
        'engagement_rate' => 0.75,
        'average_session_duration' => 44,
        'event_count' => 50,
        'conversions' => 2,
    ]);
    GoogleAnalyticsPageMetric::query()->create([
        'property_id' => '123456789',
        'metric_date' => now()->subDay()->toDateString(),
        'page_path' => '/about',
        'page_title' => 'About',
        'total_users' => 8,
        'sessions' => 12,
        'screen_page_views' => 24,
        'event_count' => 50,
        'conversions' => 2,
    ]);

    Livewire::test($widgetClass)->assertOk();
})->with([
    GoogleAnalyticsOverviewStatsWidget::class,
    GoogleAnalyticsTrafficTrendWidget::class,
    GoogleAnalyticsTopPagesWidget::class,
    GoogleAnalyticsTopPagesTableWidget::class,
    GoogleAnalyticsSetupStatusWidget::class,
]);
