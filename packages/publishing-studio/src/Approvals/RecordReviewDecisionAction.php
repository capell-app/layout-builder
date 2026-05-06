<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Approvals;

use Capell\PublishingStudio\Enums\ReviewDecisionEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Enums\WorkspaceTransitionEnum;
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Exceptions\InvalidReviewDecisionException;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceReviewAssignment;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Records a reviewer's decision on a {@see WorkspaceReviewAssignment}. When
 * every assignment on the workspace is decided Approved the workspace
 * transitions to Approved; a single Rejected decision knocks the workspace
 * back to Open and clears remaining assignments.
 */
class RecordReviewDecisionAction
{
    public function handle(
        WorkspaceReviewAssignment $assignment,
        ReviewDecisionEnum $decision,
        ?Authenticatable $actor = null,
        ?string $notes = null,
    ): Workspace {
        return DB::transaction(function () use ($assignment, $decision, $actor, $notes): Workspace {
            $workspace = Workspace::query()
                ->whereKey($assignment->workspace_id)
                ->lockForUpdate()
                ->firstOrFail();

            $assignment = WorkspaceReviewAssignment::query()
                ->whereKey($assignment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($assignment->isDecided()) {
                throw InvalidReviewDecisionException::alreadyDecided($assignment);
            }

            $assignment->forceFill([
                'decision' => $decision,
                'notes' => $notes,
                'decided_at' => CarbonImmutable::now(),
            ])->save();

            if ($decision === ReviewDecisionEnum::Rejected) {
                return $this->transitionToOpen($workspace, $actor, $notes);
            }

            $outstanding = WorkspaceReviewAssignment::query()
                ->where('workspace_id', $workspace->id)
                ->where(function (Builder $query): void {
                    $query->whereNull('decision')
                        ->orWhere('decision', '!=', ReviewDecisionEnum::Approved->value);
                })
                ->lockForUpdate()
                ->exists();

            if ($outstanding) {
                return $workspace;
            }

            return $this->transitionToApproved($workspace, $actor, $notes);
        });
    }

    private function transitionToApproved(Workspace $workspace, ?Authenticatable $actor, ?string $notes): Workspace
    {
        $previousStatus = $workspace->status;
        $workspace->forceFill([
            'status' => WorkspaceStatusEnum::Approved,
            'approved_at' => CarbonImmutable::now(),
        ])->save();

        event(new WorkspaceStateChanged(
            $workspace,
            $previousStatus,
            $workspace->status,
            WorkspaceTransitionEnum::Approved->value,
            $actor,
            $notes,
        ));

        return $workspace->refresh();
    }

    private function transitionToOpen(Workspace $workspace, ?Authenticatable $actor, ?string $notes): Workspace
    {
        $previousStatus = $workspace->status;
        $workspace->forceFill([
            'status' => WorkspaceStatusEnum::Open,
            'submitted_at' => null,
        ])->save();

        event(new WorkspaceStateChanged(
            $workspace,
            $previousStatus,
            $workspace->status,
            WorkspaceTransitionEnum::Rejected->value,
            $actor,
            $notes,
        ));

        return $workspace->refresh();
    }
}
