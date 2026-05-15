<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\Dashboard;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class LayoutHealthData extends Data
{
    /**
     * @param  Collection<int, ElementGroupData>  $elementsByGroup
     * @param  Collection<int, UnusedElementData>  $unusedElements
     * @param  Collection<int, LeastUsedElementData>  $leastUsedElements
     */
    public function __construct(
        public readonly int $totalElements,
        public readonly int $totalSections,
        public readonly int $publishedSections,
        public readonly int $draftSections,
        public readonly int $layoutsWithModifications,
        public readonly Collection $elementsByGroup,
        public readonly Collection $unusedElements,
        public readonly Collection $leastUsedElements,
    ) {}
}
