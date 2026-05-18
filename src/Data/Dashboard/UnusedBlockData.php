<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\Dashboard;

use Spatie\LaravelData\Data;

final class UnusedBlockData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $group,
    ) {}
}
