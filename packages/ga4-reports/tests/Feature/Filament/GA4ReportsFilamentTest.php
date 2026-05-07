<?php

declare(strict_types=1);

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\GA4Reports\Filament\Pages\GA4ReportsPage;
use Capell\GA4Reports\Filament\Settings\Contributors\GA4ReportsDashboardSettingsContributor;
use Capell\GA4Reports\Filament\Widgets\GA4ReportsOverviewStatsWidget;
use Capell\GA4Reports\Filament\Widgets\GA4ReportsSetupStatusWidget;
use Capell\GA4Reports\Filament\Widgets\GA4ReportsTopPagesTableWidget;
use Capell\GA4Reports\Filament\Widgets\GA4ReportsTopPagesWidget;
use Capell\GA4Reports\Filament\Widgets\GA4ReportsTrafficTrendWidget;
use Capell\GA4Reports\Models\GA4ReportsDailyMetric;
use Capell\GA4Reports\Models\GA4ReportsPageMetric;
use Capell\GA4Reports\Settings\GA4ReportsSettings;
use Capell\GA4Reports\Tests\GA4ReportsTestCase;
use Livewire\Livewire;

uses(GA4ReportsTestCase::class);

function configureGA4ReportsFilamentSettings(): void
{
    $settings = new GA4ReportsSettings;
    $settings->enabled = true;
    $settings->property_id = '123456789';
    $settings->credentials_path = '/tmp/ga4-reports.json';
    $settings->sync_days = 30;
    $settings->route_slug = 'ga4-reports';

    app()->instance(GA4ReportsSettings::class, $settings);
}

it('exposes GA4 dashboard settings keys with translated labels', function (): void {
    $entries = (new GA4ReportsDashboardSettingsContributor)->settingsKeys();

    expect(collect($entries)->pluck('key')->all())->toBe([
        'ga4_reports_overview',
        'ga4_reports_traffic_trend',
        'ga4_reports_top_pages',
        'ga4_reports_sync_status',
    ]);

    foreach ($entries as $entry) {
        expect($entry['label'])->toBeString()->not->toBe('')
            ->and(str_contains($entry['label'], 'capell-ga4-reports::'))->toBeFalse()
            ->and($entry['group'])->toBeString()->not->toBe('');
    }
});

it('registers GA4 dashboard widgets and settings contributor', function (): void {
    $contributors = collect(app()->tagged(DashboardSettingsContributor::TAG))
        ->map(fn (DashboardSettingsContributor $contributor): string => $contributor::class);

    expect($contributors)->toContain(GA4ReportsDashboardSettingsContributor::class)
        ->and(CapellAdmin::getDashboardWidgets(DashboardEnum::Main))
        ->toContain(GA4ReportsOverviewStatsWidget::class)
        ->toContain(GA4ReportsTrafficTrendWidget::class)
        ->toContain(GA4ReportsTopPagesWidget::class)
        ->toContain(GA4ReportsSetupStatusWidget::class);
});

it('uses translated page labels and configured slug', function (): void {
    configureGA4ReportsFilamentSettings();

    expect(GA4ReportsPage::getSlug())->toBe('ga4-reports')
        ->and(GA4ReportsPage::getNavigationLabel())->toBe('GA4 Reports')
        ->and(GA4ReportsPage::getNavigationGroup())->toBe('Insights')
        ->and((new GA4ReportsPage)->getTitle())->toBe('GA4 Reports');
});

it('renders GA4 dashboard widgets with empty and seeded data', function (string $widgetClass): void {
    configureGA4ReportsFilamentSettings();

    GA4ReportsDailyMetric::query()->create([
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
    GA4ReportsPageMetric::query()->create([
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
    GA4ReportsOverviewStatsWidget::class,
    GA4ReportsTrafficTrendWidget::class,
    GA4ReportsTopPagesWidget::class,
    GA4ReportsTopPagesTableWidget::class,
    GA4ReportsSetupStatusWidget::class,
]);
