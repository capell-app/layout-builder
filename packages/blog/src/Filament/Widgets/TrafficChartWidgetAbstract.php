<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Widgets;

use Capell\Admin\Filament\Widgets\CapellWidget;
use Capell\Blog\Data\Dashboard\TrafficChartData;
use Capell\Blog\Data\Dashboard\TrafficPointData;
use Capell\Core\Models\AccessLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TrafficChartWidget extends CapellWidget
{
    protected static string $settingsKey = 'traffic_chart';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected string $view = 'capell-blog::filament.widgets.traffic-chart';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['data' => $this->getData()];
    }

    private function getData(): TrafficChartData
    {
        $rows = AccessLog::query()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT session_id) as visitors'),
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $points = $rows->map(fn (object $row): TrafficPointData => new TrafficPointData(
            date: $row->date,
            views: (int) $row->views,
            visitors: (int) $row->visitors,
        ));

        return new TrafficChartData(
            totalViews: (int) $rows->sum('views'),
            totalVisitors: (int) $rows->sum('visitors'),
            points: TrafficPointData::collect($points, Collection::class),
        );
    }
}
