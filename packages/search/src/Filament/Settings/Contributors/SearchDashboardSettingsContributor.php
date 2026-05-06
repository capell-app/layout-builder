<?php

declare(strict_types=1);

namespace Capell\Search\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class SearchDashboardSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            [
                'key' => 'search_overview',
                'label' => __('capell-search::dashboard.search_overview'),
                'group' => __('capell-search::dashboard.group'),
            ],
            [
                'key' => 'top_searches',
                'label' => __('capell-search::dashboard.top_searches'),
                'group' => __('capell-search::dashboard.group'),
            ],
            [
                'key' => 'trending_searches',
                'label' => __('capell-search::dashboard.trending_searches'),
                'group' => __('capell-search::dashboard.group'),
            ],
            [
                'key' => 'zero_result_searches',
                'label' => __('capell-search::dashboard.zero_result_searches'),
                'group' => __('capell-search::dashboard.group'),
            ],
        ];
    }
}
