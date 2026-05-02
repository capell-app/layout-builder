<?php

declare(strict_types=1);

namespace Capell\Mosaic\Data\Dashboard;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class LayoutHealthData extends Data
{
    /**
     * @param  Collection<int, WidgetGroupData>  $widgetsByGroup
     * @param  Collection<int, UnusedWidgetData>  $unusedWidgets
     * @param  Collection<int, LeastUsedWidgetData>  $leastUsedWidgets
     */
    public function __construct(
        public readonly int $totalWidgets,
        public readonly int $totalSections,
        public readonly int $publishedSections,
        public readonly int $draftSections,
        public readonly int $layoutsWithModifications,
        public readonly Collection $widgetsByGroup,
        public readonly Collection $unusedWidgets,
        public readonly Collection $leastUsedWidgets,
    ) {}
}
