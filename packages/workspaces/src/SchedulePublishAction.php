<?php

declare(strict_types=1);

namespace Capell\Workspaces;

use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Enums\WorkspaceTransitionEnum;
use Capell\Workspaces\Events\WorkspaceStateChanged;
use Capell\Workspaces\Exceptions\InvalidScheduleException;
use Capell\Workspaces\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Transition an Approved workspace into the Scheduled state, recording when
 * it should be auto-published. The actual publish is performed later by
 * {@see PublishScheduledWorkspacesJob}, which subjects the workspace to the
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
