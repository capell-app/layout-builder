<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'ga4_reports.enabled' => false,
            'ga4_reports.property_id' => '',
            'ga4_reports.credentials_path' => '',
            'ga4_reports.sync_days' => 30,
            'ga4_reports.route_slug' => 'ga4-reports',
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migration->exists($key)) {
                $this->migration->add($key, $value);
            }
        }
    }
};
