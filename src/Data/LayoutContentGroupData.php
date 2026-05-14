<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutContentGroupData extends Data
{
    /**
     * @param  array<int, LayoutContentItemData>  $items
     */
    public function __construct(
        public string $key,
        public string $label,
        public ?string $summary,
        public array $items,
        public int $order,
    ) {}
}
