<?php

declare(strict_types=1);

namespace Capell\Insights\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class InsightsDashboardSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            [
                'key' => 'insights_overview',
                'label' => __('capell-insights::widgets.insights_overview'),
                'group' => __('capell-insights::settings.fieldset'),
            ],
            [
                'key' => 'insights_popular_pages',
                'label' => __('capell-insights::widgets.popular_pages'),
                'group' => __('capell-insights::settings.fieldset'),
            ],
            [
                'key' => 'insights_trending_pages',
                'label' => __('capell-insights::widgets.trending_pages'),
                'group' => __('capell-insights::settings.fieldset'),
            ],
            [
                'key' => 'insights_live_stats',
                'label' => __('capell-insights::widgets.live_statistics'),
                'group' => __('capell-insights::settings.fieldset'),
            ],
            [
                'key' => 'insights_recent_journeys',
                'label' => __('capell-insights::widgets.recent_journeys'),
                'group' => __('capell-insights::settings.fieldset'),
            ],
            [
                'key' => 'insights_top_actions',
                'label' => __('capell-insights::widgets.top_actions'),
                'group' => __('capell-insights::settings.fieldset'),
            ],
        ];
    }
}
