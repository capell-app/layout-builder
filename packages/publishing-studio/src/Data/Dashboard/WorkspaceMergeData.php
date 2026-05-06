<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Data\Dashboard;

use Spatie\LaravelData\Data;

final class WorkspaceMergeData extends Data
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly string $name,
        public readonly string $actorName,
        public readonly int $pageCount,
        public readonly int $durationOpenHours,
        public readonly string $publishedAt,
    ) {}
}
