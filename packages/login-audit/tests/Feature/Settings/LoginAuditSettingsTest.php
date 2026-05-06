<?php

declare(strict_types=1);

use Capell\LoginAudit\Actions\ApplyLoginAuditSettingsAction;
use Capell\LoginAudit\Models\LoginAudit;
use Capell\LoginAudit\Settings\LoginAuditSettings;
use Rappasoft\LaravelLoginAudit\Models\LoginAudit as VendorLoginAudit;
use Spatie\LaravelSettings\Migrations\SettingsMigrationAssistant;

function seedLoginAuditSetting(string $settingName, mixed $value): void
{
    /** @var SettingsMigrationAssistant $settingsMigrationAssistant */
    $settingsMigrationAssistant = resolve(SettingsMigrationAssistant::class);
    $settingKey = 'login_audit.' . $settingName;

    if ($settingsMigrationAssistant->exists($settingKey)) {
        $settingsMigrationAssistant->update($settingKey, $value);

        return;
    }

    $settingsMigrationAssistant->add($settingKey, $value);
}

it('uses retention days settings for the purge command configuration', function (): void {
    seedLoginAuditSetting('show_login_audits', true);
    seedLoginAuditSetting('retention_days', 42);
    seedLoginAuditSetting('track_user_ip_addresses', true);

    config()->set('login-audit.purge', 365);

    ApplyLoginAuditSettingsAction::run();

    expect(config('login-audit.purge'))->toBe(42);
});

it('removes stored ip addresses when tracking is disabled', function (): void {
    seedLoginAuditSetting('show_login_audits', true);
    seedLoginAuditSetting('retention_days', 90);
    seedLoginAuditSetting('track_user_ip_addresses', false);

    app()->forgetInstance(LoginAuditSettings::class);

    $authenticationLog = LoginAudit::factory()->create([
        'ip_address' => '203.0.113.10',
    ]);

    expect($authenticationLog->refresh()->ip_address)->toBeNull();
});

it('removes vendor-created ip addresses when tracking is disabled', function (): void {
    seedLoginAuditSetting('show_login_audits', true);
    seedLoginAuditSetting('retention_days', 90);
    seedLoginAuditSetting('track_user_ip_addresses', false);

    app()->forgetInstance(LoginAuditSettings::class);

    $authenticationLog = new VendorLoginAudit;
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
