<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class GA4ReportsDailyMetricData extends Data
{
    public function __construct(
        public readonly string $propertyId,
        public readonly CarbonImmutable $metricDate,
        public readonly int $totalUsers,
        public readonly int $sessions,
        public readonly int $screenPageViews,
        public readonly int $engagedSessions,
        public readonly float $engagementRate,
        public readonly float $averageSessionDuration,
        public readonly int $eventCount,
        public readonly int $conversions,
    ) {}
}
