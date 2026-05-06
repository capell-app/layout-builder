<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migration->exists('login_audit.show_login_audits')) {
            $this->migration->add('login_audit.show_login_audits', true);
        }

        if (! $this->migration->exists('login_audit.retention_days')) {
            $this->migration->add('login_audit.retention_days', 90);
        }

        if (! $this->migration->exists('login_audit.track_user_ip_addresses')) {
            $this->migration->add('login_audit.track_user_ip_addresses', true);
        }
    }
};
