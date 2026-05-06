<?php

declare(strict_types=1);

namespace Capell\Search\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class SearchInsightsWindowData extends Data
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}
}
