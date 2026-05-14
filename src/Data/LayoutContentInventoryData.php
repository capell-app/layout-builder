<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutContentInventoryData extends Data
{
    /**
     * @param  array<int, LayoutContentGroupData>  $groups
     */
    public function __construct(
        public array $groups,
        public int $itemCount,
        public string $signature,
    ) {}
}
