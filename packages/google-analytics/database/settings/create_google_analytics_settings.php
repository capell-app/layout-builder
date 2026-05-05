<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'google_analytics.enabled' => false,
            'google_analytics.property_id' => '',
            'google_analytics.credentials_path' => '',
            'google_analytics.sync_days' => 30,
            'google_analytics.route_slug' => 'google-analytics',
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }
};
