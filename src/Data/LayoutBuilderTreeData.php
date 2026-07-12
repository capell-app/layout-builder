<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutBuilderTreeData extends Data
{
    /**
     * @param  array<int, LayoutBuilderTreeContainerData>  $containers
     */
    public function __construct(
        public array $containers,
        public int $containerCount,
        public int $widgetCount,
        public string $signature,
    ) {}
}
