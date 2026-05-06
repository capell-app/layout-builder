<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Admin\Filament\Concerns\HasLineChartOptions;
use Capell\GA4Reports\Actions\BuildGA4ReportsTrendAction;
use Capell\GA4Reports\Data\GA4ReportsTrendPointData;
use Capell\GA4Reports\Filament\Widgets\Concerns\BuildsGA4ReportsDashboardWindow;
use Filament\Widgets\ChartWidget;

final class GA4ReportsTrafficTrendWidget extends ChartWidget implements CapellWidgetContract
{
    use BuildsGA4ReportsDashboardWindow;
    use GatedByRoleAndSettings;
    use HasLineChartOptions;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'ga4_reports_traffic_trend';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2];

    protected static ?int $sort = 22;

    public function getHeading(): string
    {
        return __('capell-ga4-reports::widgets.traffic_trend');
    }

    protected function getData(): array
    {
        $points = BuildGA4ReportsTrendAction::run($this->getGA4ReportsWindow());

        return [
            'datasets' => [
                [
                    'label' => __('capell-ga4-reports::widgets.screen_page_views'),
                    'data' => array_map(
                        fn (GA4ReportsTrendPointData $point): int => $point->screenPageViews,
                        $points,
                    ),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => __('capell-ga4-reports::widgets.sessions'),
                    'data' => array_map(
                        fn (GA4ReportsTrendPointData $point): int => $point->sessions,
                        $points,
                    ),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.12)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => array_map(
                fn (GA4ReportsTrendPointData $point): string => $point->label,
                $points,
            ),
        ];
    }
}
