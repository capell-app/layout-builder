<?php

declare(strict_types=1);

namespace Capell\DashboardReports\Data\Dashboard;

use Spatie\LaravelData\Data;

final class PublishingTrendData extends Data
{
    /**
     * @param  list<PublishingTrendPointData>  $points
     */
    public function __construct(
        public readonly array $points,
        public readonly int $totalPublished,
        public readonly int $totalScheduled,
    ) {}
}
