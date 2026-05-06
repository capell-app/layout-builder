<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class GA4ReportsWindowData extends Data
{
    public function __construct(
        public readonly CarbonImmutable $startsAt,
        public readonly CarbonImmutable $endsAt,
        public readonly string $propertyId,
    ) {}
}
