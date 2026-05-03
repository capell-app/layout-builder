<?php

declare(strict_types=1);

namespace Capell\Analytics\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class AnalyticsDashboardSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            [
                'key' => 'analytics_overview',
                'label' => __('capell-analytics::widgets.analytics_overview'),
                'group' => __('capell-analytics::settings.fieldset'),
            ],
            [
                'key' => 'analytics_popular_pages',
                'label' => __('capell-analytics::widgets.popular_pages'),
                'group' => __('capell-analytics::settings.fieldset'),
            ],
            [
                'key' => 'analytics_trending_pages',
                'label' => __('capell-analytics::widgets.trending_pages'),
                'group' => __('capell-analytics::settings.fieldset'),
            ],
            [
                'key' => 'analytics_live_stats',
                'label' => __('capell-analytics::widgets.live_statistics'),
                'group' => __('capell-analytics::settings.fieldset'),
            ],
            [
                'key' => 'analytics_recent_journeys',
                'label' => __('capell-analytics::widgets.recent_journeys'),
                'group' => __('capell-analytics::settings.fieldset'),
            ],
            [
                'key' => 'analytics_top_actions',
                'label' => __('capell-analytics::widgets.top_actions'),
                'group' => __('capell-analytics::settings.fieldset'),
            ],
        ];
    }
}
