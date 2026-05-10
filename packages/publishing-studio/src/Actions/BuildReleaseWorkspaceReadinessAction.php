<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions;

use Capell\PublishingStudio\Checks\PublishCheckResult;
use Capell\PublishingStudio\Data\ReleaseWorkspaceReadinessData;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Publisher;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

final class BuildReleaseWorkspaceReadinessAction
{
    use AsObject;

    public function handle(Workspace $workspace): ReleaseWorkspaceReadinessData
    {
        try {
            $report = app(Publisher::class)->dryRun($workspace);
        } catch (Throwable $exception) {
            return new ReleaseWorkspaceReadinessData(
                workspaceId: (int) $workspace->getKey(),
                wouldPublish: false,
                blockingIssues: [$exception->getMessage()],
                blockingIssueCount: 1,
            );
        }

        $blockingIssues = [];

        if (! $report->wouldPublish) {
            $blockingIssues[] = $report->failure?->getMessage() ?? 'Release dry run did not pass.';
        }

        if ($report->hasCollisions()) {
            $blockingIssues[] = 'Release has URL collisions.';
        }

        if ($report->hasConflicts()) {
            $blockingIssues[] = 'Release has stale workspace conflicts.';
        }

        foreach ($report->checkResults as $checkResult) {
            if (! $checkResult instanceof PublishCheckResult || ! $checkResult->isError() || $checkResult->isClean()) {
                continue;
            }

            array_push(
                $blockingIssues,
                ...($checkResult->messages === [] ? [$checkResult->label] : $checkResult->messages),
            );
        }

        $blockingIssues = array_values(array_filter($blockingIssues));

        return new ReleaseWorkspaceReadinessData(
            workspaceId: (int) $workspace->getKey(),
            wouldPublish: $report->wouldPublish && $blockingIssues === [],
            blockingIssues: $blockingIssues,
            blockingIssueCount: count($blockingIssues),
        );
    }
}
