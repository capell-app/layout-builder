<?php

declare(strict_types=1);

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\DashboardReports\Filament\Settings\Contributors\DashboardReportsDashboardSettingsContributor;
use Capell\DashboardReports\Tests\DashboardReportsTestCase;

uses(DashboardReportsTestCase::class);

it('exposes dashboard-dashboard_reports dashboard settings keys with translated labels', function (): void {
    $entries = (new DashboardReportsDashboardSettingsContributor)->settingsKeys();

    expect(collect($entries)->pluck('key')->all())->toBe([
        'publishing_trend',
        'content_health',
    ]);

    foreach ($entries as $entry) {
        expect($entry['label'])->toBeString()->not->toBe('')
            ->and(str_contains($entry['label'], 'capell-dashboard-reports::'))->toBeFalse()
            ->and($entry['group'])->toBe(__('capell-dashboard-reports::dashboard.group_dashboard-dashboard_reports'));
    }
});

it('registers the dashboard-dashboard_reports dashboard settings contributor', function (): void {
    $contributors = collect(app()->tagged(DashboardSettingsContributor::TAG))
        ->map(fn (DashboardSettingsContributor $contributor): string => $contributor::class);

    expect($contributors)->toContain(DashboardReportsDashboardSettingsContributor::class);
});
