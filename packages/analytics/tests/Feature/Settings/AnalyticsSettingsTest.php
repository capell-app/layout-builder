<?php

declare(strict_types=1);

use Capell\Analytics\Filament\Settings\AnalyticsSettingsSchema;
use Capell\Analytics\Settings\AnalyticsSettings;
use Capell\Analytics\Tests\AnalyticsTestCase;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

uses(AnalyticsTestCase::class);

it('loads analytics settings defaults', function (): void {
    /** @var SettingsMigrator $settingsMigrator */
    $settingsMigrator = app(SettingsMigrator::class);

    expect($settingsMigrator->exists('analytics.enabled'))->toBeTrue()
        ->and(app(AnalyticsSettings::class)->retention_days)->toBe(365);
});

it('normalizes textarea settings lists', function (): void {
    expect(AnalyticsSettingsSchema::listToTextarea(['/admin*', '/livewire*']))->toBe('/admin*' . PHP_EOL . '/livewire*')
        ->and(AnalyticsSettingsSchema::textareaToList("/admin*\n\n /livewire* \n"))->toBe(['/admin*', '/livewire*']);
});
