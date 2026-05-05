<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class GoogleAnalyticsWindowData extends Data
{
    public function __construct(
        public readonly CarbonImmutable $startsAt,
        public readonly CarbonImmutable $endsAt,
        public readonly string $propertyId,
    ) {}
}
