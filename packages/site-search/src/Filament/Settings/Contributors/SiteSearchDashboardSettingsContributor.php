<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class SiteSearchDashboardSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            [
                'key' => 'site_search_overview',
                'label' => __('capell-site-search::dashboard.search_overview'),
                'group' => __('capell-site-search::dashboard.group'),
            ],
            [
                'key' => 'top_searches',
                'label' => __('capell-site-search::dashboard.top_searches'),
                'group' => __('capell-site-search::dashboard.group'),
            ],
            [
                'key' => 'trending_searches',
                'label' => __('capell-site-search::dashboard.trending_searches'),
                'group' => __('capell-site-search::dashboard.group'),
            ],
            [
                'key' => 'zero_result_searches',
                'label' => __('capell-site-search::dashboard.zero_result_searches'),
                'group' => __('capell-site-search::dashboard.group'),
            ],
        ];
    }
}
