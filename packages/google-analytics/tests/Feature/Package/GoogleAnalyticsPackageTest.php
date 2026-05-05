<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsDailyMetric;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsPageMetric;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsSyncRun;
use Capell\GoogleAnalytics\Providers\GoogleAnalyticsServiceProvider;
use Capell\GoogleAnalytics\Settings\GoogleAnalyticsSettings;
use Capell\GoogleAnalytics\Settings\GoogleAnalyticsSettingsMigrationProvider;
use Capell\GoogleAnalytics\Tests\GoogleAnalyticsTestCase;
use Illuminate\Support\Facades\Schema;

uses(GoogleAnalyticsTestCase::class);

it('registers package metadata when installed', function (): void {
    $package = CapellCore::getPackage(GoogleAnalyticsServiceProvider::$packageName);

    expect($package->name)->toBe(GoogleAnalyticsServiceProvider::$packageName)
        ->and($package->isInstalled())->toBeTrue();
});

it('creates GA4 reporting tables', function (): void {
    expect(Schema::hasTable((new GoogleAnalyticsSyncRun)->getTable()))->toBeTrue()
        ->and(Schema::hasTable((new GoogleAnalyticsDailyMetric)->getTable()))->toBeTrue()
        ->and(Schema::hasTable((new GoogleAnalyticsPageMetric)->getTable()))->toBeTrue();
});

it('exposes default settings and setting migrations', function (): void {
    $settings = new GoogleAnalyticsSettings;
    $provider = new GoogleAnalyticsSettingsMigrationProvider;

    expect($settings->enabled)->toBeFalse()
        ->and($settings->property_id)->toBe('')
        ->and($settings->credentials_path)->toBe('')
        ->and($settings->sync_days)->toBe(30)
        ->and($settings->route_slug)->toBe('google-analytics')
        ->and($provider->getSettingMigrations())->toBe(['create_google_analytics_settings']);
});
