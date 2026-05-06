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
 * Soft-deletes draft publishing-studio so their audit trail is retained but they
 * disappear from editorial queues. Only Open / InReview publishing-studio are
 * discardable — anything already merged, scheduled, or published is skipped.
 */
class DiscardPublishingStudioAction
{
    use AsObject;

    /**
     * @param  Collection<int, Workspace>  $publishingStudio
     * @return array{discarded: int, skipped: int}
     */
    public function handle(Collection $publishingStudio, User $actor): array
    {
        $discardable = [
            WorkspaceStatusEnum::Open,
            WorkspaceStatusEnum::InReview,
        ];

        $discarded = 0;
        $skipped = 0;

        foreach ($publishingStudio as $workspace) {
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
