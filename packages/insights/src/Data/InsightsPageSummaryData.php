<?php

declare(strict_types=1);

namespace Capell\Insights\Data;

use Spatie\LaravelData\Data;

final class InsightsPageSummaryData extends Data
{
    public function __construct(
        public string $path,
        public int $views = 0,
        public int $visitors = 0,
        public int $events = 0,
        public ?string $title = null,
    ) {}
}
