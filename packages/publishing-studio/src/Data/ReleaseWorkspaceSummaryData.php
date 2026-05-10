<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Data;

use Spatie\LaravelData\Data;

final class ReleaseWorkspaceSummaryData extends Data
{
    /**
     * @param  list<ReleaseWorkspaceItemData>  $items
     */
    public function __construct(
        public readonly int $workspaceId,
        public readonly array $items,
        public readonly int $itemCount,
    ) {}
}
