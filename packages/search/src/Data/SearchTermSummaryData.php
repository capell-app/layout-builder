<?php

declare(strict_types=1);

namespace Capell\Search\Data;

use Spatie\LaravelData\Data;

final class SearchTermSummaryData extends Data
{
    public function __construct(
        public string $query,
        public string $normalizedQuery,
        public int $searches,
        public int $resultsCount,
        public float $trendPercentage = 0.0,
    ) {}
}
