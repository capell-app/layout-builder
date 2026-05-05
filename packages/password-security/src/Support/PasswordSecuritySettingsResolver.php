<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Support;

use Capell\PasswordSecurity\Data\ResolvedPasswordSecuritySettingsData;
use Capell\PasswordSecurity\Settings\PasswordSecuritySettings;
use Spatie\LaravelSettings\Exceptions\MissingSettings;

class PasswordSecuritySettingsResolver
{
    public function settings(): ResolvedPasswordSecuritySettingsData
    {
        try {
            $settings = resolve(PasswordSecuritySettings::class);

            return new ResolvedPasswordSecuritySettingsData(
                passwordExpiryEnabled: $settings->password_expiry_enabled,
                passwordExpiryDays: $settings->password_expiry_days,
                forceChangeEnabled: $settings->force_change_enabled,
                compromisedPasswordChecksEnabled: $settings->compromised_password_checks_enabled,
                passwordHistoryEnabled: $settings->password_history_enabled,
                passwordHistoryCount: $settings->password_history_count,
            );
        } catch (MissingSettings) {
            return new ResolvedPasswordSecuritySettingsData;
        }
    }
}
