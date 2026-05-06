<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Support;

use Capell\PasswordPolicy\Data\ResolvedPasswordPolicySettingsData;
use Capell\PasswordPolicy\Settings\PasswordPolicySettings;
use Spatie\LaravelSettings\Exceptions\MissingSettings;

class PasswordPolicySettingsResolver
{
    public function settings(): ResolvedPasswordPolicySettingsData
    {
        try {
            $settings = resolve(PasswordPolicySettings::class);

            return new ResolvedPasswordPolicySettingsData(
                passwordExpiryEnabled: $settings->password_expiry_enabled,
                passwordExpiryDays: $settings->password_expiry_days,
                forceChangeEnabled: $settings->force_change_enabled,
                compromisedPasswordChecksEnabled: $settings->compromised_password_checks_enabled,
                passwordHistoryEnabled: $settings->password_history_enabled,
                passwordHistoryCount: $settings->password_history_count,
            );
        } catch (MissingSettings) {
            return new ResolvedPasswordPolicySettingsData;
        }
    }
}
