<?php

declare(strict_types=1);

namespace Capell\SeoTools\Data;

use Capell\SeoTools\Enums\SearchConsoleMetricEnum;
use Capell\SeoTools\Enums\SeoIssueSeverityEnum;
use Spatie\LaravelData\Data;

class SearchConsoleInsightData extends Data
{
    public function __construct(
        public SearchConsoleMetricEnum $metric,
        public string $message,
        public int|float|string|null $value = null,
        public int|float|string|null $previousValue = null,
        public ?float $delta = null,
        public SeoIssueSeverityEnum $severity = SeoIssueSeverityEnum::Notice,
    ) {}
}
