<?php

declare(strict_types=1);

namespace Capell\Workspaces\Actions;

use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Soft-deletes draft workspaces so their audit trail is retained but they
 * disappear from editorial queues. Only Open / InReview workspaces are
 * discardable — anything already merged, scheduled, or published is skipped.
 */
class DiscardWorkspacesAction
{
    use AsObject;

    /**
     * @param  Collection<int, Workspace>  $workspaces
     * @return array{discarded: int, skipped: int}
     */
    public function handle(Collection $workspaces, User $actor): array
    {
        $discardable = [
            WorkspaceStatusEnum::Open,
            WorkspaceStatusEnum::InReview,
        ];

        $discarded = 0;
        $skipped = 0;

        foreach ($workspaces as $workspace) {
            $canDelete = Gate::forUser($actor)->inspect('delete', $workspace)->allowed();
            $isDiscardable = in_array($workspace->status, $discardable, true);

            if (! $canDelete || ! $isDiscardable) {
                $skipped++;

                continue;
            }

            $workspace->delete();
            $discarded++;
        }

        return ['discarded' => $discarded, 'skipped' => $skipped];
    }
}
