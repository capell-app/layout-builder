<?php

declare(strict_types=1);

namespace Capell\Blog\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class ArchiveMonthData extends Data
{
    public function __construct(
        public int $year,
        public int $month,
        public int $total = 0,
    ) {}

    public static function fromDate(CarbonImmutable $date): self
    {
        return new self(
            year: (int) $date->format('Y'),
            month: (int) $date->format('m'),
        );
    }

    public function getDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m', sprintf('%d-%d', $this->year, $this->month));
    }
}
