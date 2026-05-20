<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\Dashboard;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class LayoutHealthData extends Data
{
    /**
     * @param  Collection<int, BlockGroupData>  $blocksByGroup
     * @param  Collection<int, UnusedBlockData>  $unusedBlocks
     * @param  Collection<int, LeastUsedBlockData>  $leastUsedBlocks
     */
    public function __construct(
        public readonly int $totalBlocks,
        public readonly int $totalSections,
        public readonly int $publishedSections,
        public readonly int $draftSections,
        public readonly int $layoutsWithModifications,
        public readonly Collection $blocksByGroup,
        public readonly Collection $unusedBlocks,
        public readonly Collection $leastUsedBlocks,
    ) {}
}
