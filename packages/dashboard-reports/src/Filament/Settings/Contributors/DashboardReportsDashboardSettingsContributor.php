<?php

declare(strict_types=1);

namespace Capell\DashboardReports\Filament\Settings\Contributors;

use Capell\Admin\Contracts\DashboardSettingsContributor;

final class DashboardReportsDashboardSettingsContributor implements DashboardSettingsContributor
{
    /**
     * @return list<array{key: string, label: string, group: string}>
     */
    public function settingsKeys(): array
    {
        return [
            [
                'key' => 'publishing_trend',
                'label' => __('capell-dashboard-reports::dashboard.widget_publishing_trend'),
                'group' => __('capell-dashboard-reports::dashboard.group_dashboard-dashboard_reports'),
            ],
            [
                'key' => 'content_health',
                'label' => __('capell-dashboard-reports::dashboard.widget_content_health'),
                'group' => __('capell-dashboard-reports::dashboard.group_dashboard-dashboard_reports'),
            ],
        ];
    }
}
