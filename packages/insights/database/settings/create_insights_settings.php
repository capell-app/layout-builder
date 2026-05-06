<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'insights.enabled' => true,
            'insights.track_page_views' => true,
            'insights.track_clicks' => true,
            'insights.track_form-builder' => false,
            'insights.automatic_click_tracking' => true,
            'insights.require_consent_for_all_regions' => false,
            'insights.default_consent_region' => null,
            'insights.policy_version' => '1.0',
            'insights.retention_days' => 365,
            'insights.hash_visitor_data' => true,
            'insights.hash_salt' => 'capell-insights',
            'insights.ignored_paths' => ['/admin*', '/livewire*', '/capell/insights*', '/_debugbar*', '/_clockwork*', '/storage*'],
            'insights.ignored_selectors' => ['[data-capell-insights-ignore]', '[wire\\:click]'],
            'insights.route_prefix' => 'capell/insights',
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migration->exists($key)) {
                $this->migration->add($key, $value);
            }
        }
    }
};
