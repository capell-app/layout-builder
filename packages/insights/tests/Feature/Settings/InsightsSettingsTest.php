<?php

declare(strict_types=1);

use Capell\Insights\Filament\Settings\InsightsSettingsSchema;
use Capell\Insights\Settings\InsightsSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigrationAssistant;

it('loads insights settings defaults', function (): void {
    /** @var SettingsMigrationAssistant $settingsMigrationAssistant */
    $settingsMigrationAssistant = resolve(SettingsMigrationAssistant::class);
    $expectedKeys = [
        'insights.enabled',
        'insights.track_page_views',
        'insights.track_clicks',
        'insights.track_form-builder',
        'insights.automatic_click_tracking',
        'insights.require_consent_for_all_regions',
        'insights.default_consent_region',
        'insights.policy_version',
        'insights.retention_days',
        'insights.hash_visitor_data',
        'insights.hash_salt',
        'insights.ignored_paths',
        'insights.ignored_selectors',
        'insights.route_prefix',
    ];

    foreach ($expectedKeys as $expectedKey) {
        expect($settingsMigrationAssistant->exists($expectedKey))->toBeTrue();
    }

    expect(resolve(InsightsSettings::class)->retention_days)->toBe(365);
});

it('normalizes textarea settings lists', function (): void {
    expect(InsightsSettingsSchema::listToTextarea(['/admin*', '/livewire*']))->toBe('/admin*' . PHP_EOL . '/livewire*')
        ->and(InsightsSettingsSchema::textareaToList("/admin*\n\n /livewire* \n"))->toBe(['/admin*', '/livewire*']);
});
