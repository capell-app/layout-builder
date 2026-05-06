<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Admin\Filament\Concerns\HasDashboardDateRange;
use Capell\Blog\Data\Dashboard\TrafficChartData;
use Capell\Blog\Data\Dashboard\TrafficPointData;
use Capell\Insights\Enums\InsightsEventType;
use Capell\Insights\Models\InsightsEvent;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TrafficChartWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;
    use HasDashboardDateRange;

    protected static string $settingsKey = 'traffic_chart';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected string $view = 'capell-blog::filament.widgets.traffic-chart';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full'];

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['data' => $this->getData()];
    }

    private function getData(): TrafficChartData
    {
        [$rangeStart, $rangeEnd] = $this->getDashboardDateRange();

        $rows = InsightsEvent::query()
            ->select(
                DB::raw('DATE(occurred_at) as date'),
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT visit_id) as visitors'),
            )
            ->where('type', InsightsEventType::PageView)
            ->where('occurred_at', '>=', $rangeStart)
            ->where('occurred_at', '<=', $rangeEnd)
            ->groupBy(DB::raw('DATE(occurred_at)'))
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
