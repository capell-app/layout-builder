<?php

declare(strict_types=1);

namespace Capell\Mosaic\Data\Dashboard;

use Spatie\LaravelData\Data;

final class UnusedWidgetData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $group,
    ) {}
}
