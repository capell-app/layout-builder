<?php

declare(strict_types=1);

namespace Capell\Mosaic\Data\Dashboard;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class RecentActivityData extends Data
{
    /**
     * @param  Collection<int, ActivityItemData>  $items
     */
    public function __construct(
        public readonly Collection $items,
    ) {}
}
