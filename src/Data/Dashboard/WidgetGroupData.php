<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\Dashboard;

use Spatie\LaravelData\Data;

final class WidgetGroupData extends Data
{
    public function __construct(
        public readonly string $group,
        public readonly int $count,
        public readonly int $published,
        public readonly int $pending,
        public readonly int $expired,
    ) {}
}
