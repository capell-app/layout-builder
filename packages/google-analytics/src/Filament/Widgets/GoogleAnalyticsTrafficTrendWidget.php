<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Admin\Filament\Concerns\HasLineChartOptions;
use Capell\GoogleAnalytics\Actions\BuildGoogleAnalyticsTrendAction;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsTrendPointData;
use Capell\GoogleAnalytics\Filament\Widgets\Concerns\BuildsGoogleAnalyticsDashboardWindow;
use Filament\Widgets\ChartWidget;

final class GoogleAnalyticsTrafficTrendWidget extends ChartWidget implements CapellWidgetContract
{
    use BuildsGoogleAnalyticsDashboardWindow;
    use GatedByRoleAndSettings;
    use HasLineChartOptions;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'google_analytics_traffic_trend';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2];

    protected static ?int $sort = 22;

    public function getHeading(): string
    {
        return __('capell-google-analytics::widgets.traffic_trend');
    }

    protected function getData(): array
    {
        $points = BuildGoogleAnalyticsTrendAction::run($this->getGoogleAnalyticsWindow());

        return [
            'datasets' => [
                [
                    'label' => __('capell-google-analytics::widgets.screen_page_views'),
                    'data' => array_map(
                        fn (GoogleAnalyticsTrendPointData $point): int => $point->screenPageViews,
                        $points,
                    ),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => __('capell-google-analytics::widgets.sessions'),
                    'data' => array_map(
                        fn (GoogleAnalyticsTrendPointData $point): int => $point->sessions,
                        $points,
                    ),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.12)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => array_map(
                fn (GoogleAnalyticsTrendPointData $point): string => $point->label,
                $points,
            ),
        ];
    }
}
