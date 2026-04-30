<?php

declare(strict_types=1);

use Capell\Analytics\Filament\Settings\AnalyticsSettingsSchema;
use Capell\Analytics\Settings\AnalyticsSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

it('loads analytics settings defaults', function (): void {
    /** @var SettingsMigrator $settingsMigrator */
    $settingsMigrator = app(SettingsMigrator::class);
    $expectedKeys = [
        'analytics.enabled',
        'analytics.track_page_views',
        'analytics.track_clicks',
        'analytics.track_forms',
        'analytics.automatic_click_tracking',
        'analytics.require_consent_for_all_regions',
        'analytics.default_consent_region',
        'analytics.policy_version',
        'analytics.retention_days',
        'analytics.hash_visitor_data',
        'analytics.hash_salt',
        'analytics.ignored_paths',
        'analytics.ignored_selectors',
        'analytics.route_prefix',
    ];

    foreach ($expectedKeys as $expectedKey) {
        expect($settingsMigrator->exists($expectedKey))->toBeTrue();
    }

    expect(app(AnalyticsSettings::class)->retention_days)->toBe(365);
});

it('normalizes textarea settings lists', function (): void {
    expect(AnalyticsSettingsSchema::listToTextarea(['/admin*', '/livewire*']))->toBe('/admin*' . PHP_EOL . '/livewire*')
        ->and(AnalyticsSettingsSchema::textareaToList("/admin*\n\n /livewire* \n"))->toBe(['/admin*', '/livewire*']);
});
