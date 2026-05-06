<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\Core\Contracts\SettingsSchemaContract;
use Capell\PasswordPolicy\Filament\Settings\PasswordPolicySettingsSchema;
use Spatie\LaravelSettings\Settings;

class PasswordPolicySettings extends Settings implements SettingsContract, SettingsSchemaContract
{
    public bool $password_expiry_enabled;

    public int $password_expiry_days;

    public bool $force_change_enabled;

    public bool $compromised_password_checks_enabled;

    public bool $password_history_enabled;

    public int $password_history_count;

    public static function group(): string
    {
        return 'password_policy';
    }

    public static function schema(): string
    {
        return PasswordPolicySettingsSchema::class;
    }

    public static function instance(): self
    {
        return resolve(self::class);
    }

    public function refresh(): self
    {
        parent::refresh();

        return $this;
    }
}
