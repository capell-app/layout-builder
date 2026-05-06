<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions;

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Moves Open publishing-studio into InReview status (one-step handoff to reviewers).
 * PublishingStudio not in Open status are silently skipped; the returned count
 * reflects only records actually updated.
 */
class RequestReviewBulkAction
{
    use AsObject;

    /**
     * @param  Collection<int, Workspace>  $publishingStudio
     * @return array{requested: int, skipped: int}
     */
    public function handle(Collection $publishingStudio, User $actor): array
    {
        $requested = 0;
        $skipped = 0;

        foreach ($publishingStudio as $workspace) {
            $canUpdate = Gate::forUser($actor)->inspect('update', $workspace)->allowed();
            $isOpen = $workspace->status === WorkspaceStatusEnum::Open;

            if (! $canUpdate || ! $isOpen) {
                $skipped++;

                continue;
            }

            $workspace->status = WorkspaceStatusEnum::InReview;
            $workspace->save();

            $requested++;
        }

        return ['requested' => $requested, 'skipped' => $skipped];
    }
}
