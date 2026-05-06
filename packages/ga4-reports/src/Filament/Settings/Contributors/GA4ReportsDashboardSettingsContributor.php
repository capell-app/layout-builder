<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class GA4ReportsDashboardSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            [
                'key' => 'ga4_reports_overview',
                'label' => __('capell-ga4-reports::widgets.overview'),
                'group' => __('capell-ga4-reports::settings.fieldset'),
            ],
            [
                'key' => 'ga4_reports_traffic_trend',
                'label' => __('capell-ga4-reports::widgets.traffic_trend'),
                'group' => __('capell-ga4-reports::settings.fieldset'),
            ],
            [
                'key' => 'ga4_reports_top_pages',
                'label' => __('capell-ga4-reports::widgets.top_pages'),
                'group' => __('capell-ga4-reports::settings.fieldset'),
            ],
            [
                'key' => 'ga4_reports_sync_status',
                'label' => __('capell-ga4-reports::widgets.sync_status'),
                'group' => __('capell-ga4-reports::settings.fieldset'),
            ],
        ];
    }
}
