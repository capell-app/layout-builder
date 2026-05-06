<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Enums\WorkspaceTransitionEnum;
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Exceptions\InvalidScheduleException;
use Capell\PublishingStudio\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Transition an Approved workspace into the Scheduled state, recording when
 * it should be auto-published. The actual publish is performed later by
 * {@see PublishScheduledPublishingStudioJob}, which subjects the workspace to the
 * usual ReleaseWindowGuard checks.
 */
class SchedulePublishAction
{
    public function schedule(
        Workspace $workspace,
        CarbonImmutable $scheduledFor,
        ?Authenticatable $actor = null,
        ?string $notes = null,
    ): Workspace {
        if ($workspace->status !== WorkspaceStatusEnum::Approved
            && $workspace->status !== WorkspaceStatusEnum::Scheduled) {
            throw InvalidScheduleException::wrongStatus($workspace, $scheduledFor);
        }

        if ($scheduledFor->lessThanOrEqualTo(CarbonImmutable::now())) {
            throw InvalidScheduleException::mustBeInFuture($workspace, $scheduledFor);
        }

        $previousStatus = $workspace->status;

        $workspace->status = WorkspaceStatusEnum::Scheduled;
        $workspace->publish_at = $scheduledFor;
        $workspace->save();

        event(new WorkspaceStateChanged(
            $workspace,
            $previousStatus,
            $workspace->status,
            WorkspaceTransitionEnum::Scheduled->value,
            $actor,
            $notes,
        ));

        return $workspace->refresh();
    }

    /**
     * Cancel a pending schedule and return the workspace to Approved state.
     */
    public function unschedule(
        Workspace $workspace,
        ?Authenticatable $actor = null,
        ?string $notes = null,
    ): Workspace {
        if ($workspace->status !== WorkspaceStatusEnum::Scheduled) {
            return $workspace;
        }

        $previousStatus = $workspace->status;

        $workspace->status = WorkspaceStatusEnum::Approved;
        $workspace->publish_at = null;
        $workspace->save();

        event(new WorkspaceStateChanged(
            $workspace,
            $previousStatus,
            $workspace->status,
            WorkspaceTransitionEnum::Unscheduled->value,
            $actor,
            $notes,
        ));

        return $workspace->refresh();
    }
}
