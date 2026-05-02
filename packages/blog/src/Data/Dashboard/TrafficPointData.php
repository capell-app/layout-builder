<?php

declare(strict_types=1);

namespace Capell\Blog\Data\Dashboard;

use Spatie\LaravelData\Data;

final class TrafficPointData extends Data
{
    public function __construct(
        public readonly string $date,
        public readonly int $views,
        public readonly int $visitors,
    ) {}
}
