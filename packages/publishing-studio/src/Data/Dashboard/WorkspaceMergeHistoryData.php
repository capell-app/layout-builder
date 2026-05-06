<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Data\Dashboard;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class WorkspaceMergeHistoryData extends Data
{
    /**
     * @param  DataCollection<int, MergeHistoryEntryData>  $entries
     */
    public function __construct(
        public readonly DataCollection $entries,
    ) {}
}
