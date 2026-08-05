<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\Dashboard;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class LayoutHealthData extends Data
{
    /**
     * @param  Collection<int, LayoutHealthWorkQueueItemData>  $workQueue
     */
    public function __construct(
        public readonly Collection $workQueue,
    ) {}
}
