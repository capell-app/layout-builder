<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('authentication_log.show_authentication_logs')) {
            $this->migrator->add('authentication_log.show_authentication_logs', true);
        }

        if (! $this->migrator->exists('authentication_log.retention_days')) {
            $this->migrator->add('authentication_log.retention_days', 90);
        }

        if (! $this->migrator->exists('authentication_log.track_user_ip_addresses')) {
            $this->migrator->add('authentication_log.track_user_ip_addresses', true);
        }
    }
};
