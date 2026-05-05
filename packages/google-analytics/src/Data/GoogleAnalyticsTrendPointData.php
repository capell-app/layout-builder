<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Data;

use Spatie\LaravelData\Data;

final class GoogleAnalyticsTrendPointData extends Data
{
    public function __construct(
        public readonly string $label,
        public readonly int $screenPageViews,
        public readonly int $sessions,
        public readonly int $totalUsers,
    ) {}
}
