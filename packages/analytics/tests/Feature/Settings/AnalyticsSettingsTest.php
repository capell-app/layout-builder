<?php

declare(strict_types=1);

use Capell\Analytics\Settings\AnalyticsSettings;
use Capell\Analytics\Tests\AnalyticsTestCase;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

uses(AnalyticsTestCase::class);

it('loads analytics settings defaults', function (): void {
    /** @var SettingsMigrator $settingsMigrator */
    $settingsMigrator = app(SettingsMigrator::class);

    $settingsMigration = require dirname(__DIR__, 3) . '/database/settings/create_analytics_settings.php';
    if (method_exists($settingsMigration, 'setMigrator')) {
        $settingsMigration->setMigrator($settingsMigrator);
    }
    $settingsMigration->up();

    expect($settingsMigrator->exists('analytics.enabled'))->toBeTrue()
        ->and(app(AnalyticsSettings::class)->retention_days)->toBe(365);
});
