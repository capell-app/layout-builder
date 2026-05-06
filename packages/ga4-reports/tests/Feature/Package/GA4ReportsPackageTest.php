<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\GA4Reports\Models\GA4ReportsDailyMetric;
use Capell\GA4Reports\Models\GA4ReportsPageMetric;
use Capell\GA4Reports\Models\GA4ReportsSyncRun;
use Capell\GA4Reports\Providers\GA4ReportsServiceProvider;
use Capell\GA4Reports\Settings\GA4ReportsSettings;
use Capell\GA4Reports\Settings\GA4ReportsSettingsMigrationProvider;
use Capell\GA4Reports\Tests\GA4ReportsTestCase;
use Illuminate\Support\Facades\Schema;

uses(GA4ReportsTestCase::class);

it('registers package metadata when installed', function (): void {
    $package = CapellCore::getPackage(GA4ReportsServiceProvider::$packageName);

    expect($package->name)->toBe(GA4ReportsServiceProvider::$packageName)
        ->and($package->isInstalled())->toBeTrue();
});

it('creates GA4 reporting tables', function (): void {
    expect(Schema::hasTable((new GA4ReportsSyncRun)->getTable()))->toBeTrue()
        ->and(Schema::hasTable((new GA4ReportsDailyMetric)->getTable()))->toBeTrue()
        ->and(Schema::hasTable((new GA4ReportsPageMetric)->getTable()))->toBeTrue();
});

it('exposes default settings and setting migrations', function (): void {
    $settings = new GA4ReportsSettings;
    $provider = new GA4ReportsSettingsMigrationProvider;

    expect($settings->enabled)->toBeFalse()
        ->and($settings->property_id)->toBe('')
        ->and($settings->credentials_path)->toBe('')
        ->and($settings->sync_days)->toBe(30)
        ->and($settings->route_slug)->toBe('ga4-reports')
        ->and($provider->getSettingMigrations())->toBe(['create_ga4_reports_settings']);
});
