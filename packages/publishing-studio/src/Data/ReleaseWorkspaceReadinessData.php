<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Data;

use Spatie\LaravelData\Data;

final class ReleaseWorkspaceReadinessData extends Data
{
    /**
     * @param  list<string>  $blockingIssues
     */
    public function __construct(
        public readonly int $workspaceId,
        public readonly bool $wouldPublish,
        public readonly array $blockingIssues,
        public readonly int $blockingIssueCount,
    ) {}
}
