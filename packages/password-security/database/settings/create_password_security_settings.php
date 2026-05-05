<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('password_security.password_expiry_enabled')) {
            $this->migrator->add('password_security.password_expiry_enabled', false);
        }

        if (! $this->migrator->exists('password_security.password_expiry_days')) {
            $this->migrator->add('password_security.password_expiry_days', 90);
        }

        if (! $this->migrator->exists('password_security.force_change_enabled')) {
            $this->migrator->add('password_security.force_change_enabled', false);
        }

        if (! $this->migrator->exists('password_security.compromised_password_checks_enabled')) {
            $this->migrator->add('password_security.compromised_password_checks_enabled', false);
        }

        if (! $this->migrator->exists('password_security.password_history_enabled')) {
            $this->migrator->add('password_security.password_history_enabled', false);
        }

        if (! $this->migrator->exists('password_security.password_history_count')) {
            $this->migrator->add('password_security.password_history_count', 5);
        }
    }
};
