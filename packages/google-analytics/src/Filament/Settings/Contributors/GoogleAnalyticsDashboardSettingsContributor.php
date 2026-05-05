<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class GoogleAnalyticsDashboardSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            [
                'key' => 'google_analytics_overview',
                'label' => __('capell-google-analytics::widgets.overview'),
                'group' => __('capell-google-analytics::settings.fieldset'),
            ],
            [
                'key' => 'google_analytics_traffic_trend',
                'label' => __('capell-google-analytics::widgets.traffic_trend'),
                'group' => __('capell-google-analytics::settings.fieldset'),
            ],
            [
                'key' => 'google_analytics_top_pages',
                'label' => __('capell-google-analytics::widgets.top_pages'),
                'group' => __('capell-google-analytics::settings.fieldset'),
            ],
            [
                'key' => 'google_analytics_sync_status',
                'label' => __('capell-google-analytics::widgets.sync_status'),
                'group' => __('capell-google-analytics::settings.fieldset'),
            ],
        ];
    }
}
