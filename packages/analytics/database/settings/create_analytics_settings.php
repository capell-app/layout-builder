<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'analytics.enabled' => true,
            'analytics.track_page_views' => true,
            'analytics.track_clicks' => true,
            'analytics.track_forms' => false,
            'analytics.automatic_click_tracking' => true,
            'analytics.require_consent_for_all_regions' => false,
            'analytics.default_consent_region' => null,
            'analytics.policy_version' => '1.0',
            'analytics.retention_days' => 365,
            'analytics.hash_visitor_data' => true,
            'analytics.hash_salt' => 'capell-analytics',
            'analytics.ignored_paths' => ['/admin*', '/livewire*', '/capell/analytics*', '/_debugbar*', '/_clockwork*', '/storage*'],
            'analytics.ignored_selectors' => ['[data-capell-analytics-ignore]', '[wire\\:click]'],
            'analytics.route_prefix' => 'capell/analytics',
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }
};
