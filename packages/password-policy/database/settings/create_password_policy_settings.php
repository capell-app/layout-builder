<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migration->exists('password_policy.password_expiry_enabled')) {
            $this->migration->add('password_policy.password_expiry_enabled', false);
        }

        if (! $this->migration->exists('password_policy.password_expiry_days')) {
            $this->migration->add('password_policy.password_expiry_days', 90);
        }

        if (! $this->migration->exists('password_policy.force_change_enabled')) {
            $this->migration->add('password_policy.force_change_enabled', false);
        }

        if (! $this->migration->exists('password_policy.compromised_password_checks_enabled')) {
            $this->migration->add('password_policy.compromised_password_checks_enabled', false);
        }

        if (! $this->migration->exists('password_policy.password_history_enabled')) {
            $this->migration->add('password_policy.password_history_enabled', false);
        }

        if (! $this->migration->exists('password_policy.password_history_count')) {
            $this->migration->add('password_policy.password_history_count', 5);
        }
    }
};
