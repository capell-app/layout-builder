<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutBuilderTreeContainerData extends Data
{
    /**
     * @param  array<int, LayoutBuilderTreeBlockData>  $blocks
     */
    public function __construct(
        public string $nodeId,
        public string $key,
        public string $label,
        public ?string $areaLabel,
        public int $blockCount,
        public bool $isSelected,
        public array $blocks,
    ) {}
}
