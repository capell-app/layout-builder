<?php

declare(strict_types=1);

use Capell\AuthenticationLog\Actions\ApplyAuthenticationLogSettingsAction;
use Capell\AuthenticationLog\Models\AuthenticationLog;
use Capell\AuthenticationLog\Settings\AuthenticationLogSettings;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog as VendorAuthenticationLog;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

function seedAuthenticationLogSetting(string $settingName, mixed $value): void
{
    /** @var SettingsMigrator $settingsMigrator */
    $settingsMigrator = resolve(SettingsMigrator::class);
    $settingKey = 'authentication_log.' . $settingName;

    if ($settingsMigrator->exists($settingKey)) {
        $settingsMigrator->update($settingKey, $value);

        return;
    }

    $settingsMigrator->add($settingKey, $value);
}

it('uses retention days settings for the purge command configuration', function (): void {
    seedAuthenticationLogSetting('show_authentication_logs', true);
    seedAuthenticationLogSetting('retention_days', 42);
    seedAuthenticationLogSetting('track_user_ip_addresses', true);

    config()->set('authentication-log.purge', 365);

    ApplyAuthenticationLogSettingsAction::run();

    expect(config('authentication-log.purge'))->toBe(42);
});

it('removes stored ip addresses when tracking is disabled', function (): void {
    seedAuthenticationLogSetting('show_authentication_logs', true);
    seedAuthenticationLogSetting('retention_days', 90);
    seedAuthenticationLogSetting('track_user_ip_addresses', false);

    app()->forgetInstance(AuthenticationLogSettings::class);

    $authenticationLog = AuthenticationLog::factory()->create([
        'ip_address' => '203.0.113.10',
    ]);

    expect($authenticationLog->refresh()->ip_address)->toBeNull();
});

it('removes vendor-created ip addresses when tracking is disabled', function (): void {
    seedAuthenticationLogSetting('show_authentication_logs', true);
    seedAuthenticationLogSetting('retention_days', 90);
    seedAuthenticationLogSetting('track_user_ip_addresses', false);

    app()->forgetInstance(AuthenticationLogSettings::class);

    $authenticationLog = new VendorAuthenticationLog;
    $authenticationLog->forceFill([
        'authenticatable_type' => 'user',
        'authenticatable_id' => 999_999,
        'ip_address' => '203.0.113.10',
        'user_agent' => 'Capell Test Browser',
        'login_at' => now(),
        'login_successful' => true,
    ]);
    $authenticationLog->save();

    expect($authenticationLog->refresh()->ip_address)->toBeNull();
});
