<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Data;

use Spatie\LaravelData\Data;

final class GA4ReportsOverviewData extends Data
{
    public function __construct(
        public readonly int $totalUsers,
        public readonly int $sessions,
        public readonly int $screenPageViews,
        public readonly int $conversions,
        public readonly float $engagementRate,
        public readonly float $averageSessionDuration,
    ) {}
}
