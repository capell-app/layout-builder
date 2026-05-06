<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Data\Dashboard;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class WorkspaceActivityData extends Data
{
    /**
     * @param  DataCollection<int, WorkspaceMergeData>  $recentMerges
     */
    public function __construct(
        public readonly int $pendingApprovalsCount,
        public readonly int $stuckCount,
        public readonly DataCollection $recentMerges,
    ) {}
}
