<?php

declare(strict_types=1);

namespace Capell\DashboardReports\Data\Dashboard;

use Spatie\LaravelData\Data;

final class PublishingTrendPointData extends Data
{
    public function __construct(
        public readonly string $label,
        public readonly int $publishedCount,
        public readonly int $scheduledCount,
    ) {}
}
