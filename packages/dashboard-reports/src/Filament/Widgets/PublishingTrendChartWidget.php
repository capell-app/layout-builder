<?php

declare(strict_types=1);

namespace Capell\DashboardReports\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Admin\Filament\Concerns\HasDashboardDateRange;
use Capell\Admin\Filament\Concerns\HasLineChartOptions;
use Capell\DashboardReports\Actions\Dashboard\BuildPublishingTrendAction;
use Capell\DashboardReports\Data\Dashboard\PublishingTrendPointData;
use Filament\Widgets\ChartWidget;

final class PublishingTrendChartWidget extends ChartWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;
    use HasDashboardDateRange;
    use HasLineChartOptions;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['editor', 'admin', 'super_admin'];

    protected static string $settingsKey = 'publishing_trend';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2];

    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return __('capell-dashboard-reports::dashboard.widget_publishing_trend');
    }

    protected function getData(): array
    {
        $data = BuildPublishingTrendAction::run($this->dashboardPeriod);

        return [
            'datasets' => [
                [
                    'label' => __('capell-dashboard-reports::dashboard.chart_published_pages'),
                    'data' => array_map(
                        fn (PublishingTrendPointData $point): int => $point->publishedCount,
                        $data->points,
                    ),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => __('capell-dashboard-reports::dashboard.chart_scheduled_pages'),
                    'data' => array_map(
                        fn (PublishingTrendPointData $point): int => $point->scheduledCount,
                        $data->points,
                    ),
                    'borderColor' => '#d97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.12)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => array_map(
                fn (PublishingTrendPointData $point): string => $point->label,
                $data->points,
            ),
        ];
    }
}
