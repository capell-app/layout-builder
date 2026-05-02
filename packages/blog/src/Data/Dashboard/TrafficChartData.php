<?php

declare(strict_types=1);

namespace Capell\Blog\Data\Dashboard;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class TrafficChartData extends Data
{
    /**
     * @param  Collection<int, TrafficPointData>  $points
     */
    public function __construct(
        public readonly int $totalViews,
        public readonly int $totalVisitors,
        public readonly Collection $points,
    ) {}
}
