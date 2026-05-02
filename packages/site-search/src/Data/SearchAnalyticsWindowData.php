<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class SearchAnalyticsWindowData extends Data
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}
}
