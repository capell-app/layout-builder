<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\Dashboard;

use Spatie\LaravelData\Data;

final class LeastUsedWidgetData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly int $layoutCount,
        public readonly string $group,
    ) {}
}
