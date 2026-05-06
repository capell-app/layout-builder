<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions\Dashboard;

use Capell\Core\Models\Page;
use Capell\PublishingStudio\Data\Dashboard\WorkspaceActivityData;
use Capell\PublishingStudio\Data\Dashboard\WorkspaceMergeData;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceReviewAssignment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelData\DataCollection;

/**
 * @method static WorkspaceActivityData run(Authenticatable $user, int $stuckDays = 7, int $mergesLimit = 5)
 */
final class BuildWorkspaceActivityAction
{
    use AsAction;

    public function handle(
        Authenticatable $user,
        int $stuckDays = 7,
        int $mergesLimit = 5,
    ): WorkspaceActivityData {
        $pendingApprovalsCount = $this->countPendingApprovals($user);
        $stuckCount = $this->countStuck($stuckDays);
        $recentMerges = $this->buildRecentMerges($mergesLimit);

        return new WorkspaceActivityData(
            pendingApprovalsCount: $pendingApprovalsCount,
            stuckCount: $stuckCount,
            recentMerges: WorkspaceMergeData::collect($recentMerges, DataCollection::class),
        );
    }

    private function countPendingApprovals(Authenticatable $user): int
    {
        $morphClass = method_exists($user, 'getMorphClass')
            ? $user->getMorphClass()
            : $user::class;

        return WorkspaceReviewAssignment::query()
            ->where('reviewer_type', $morphClass)
            ->where('reviewer_id', $user->getAuthIdentifier())
            ->whereNull('decision')
            ->count();
    }

    private function countStuck(int $stuckDays): int
    {
        return Workspace::query()->withoutGlobalScopes()
            ->whereIn('status', [
                WorkspaceStatusEnum::Open->value,
                WorkspaceStatusEnum::InReview->value,
            ])
            ->where('created_at', '<', now()->subDays($stuckDays))
            ->count();
    }

    /**
     * @return array<int, WorkspaceMergeData>
     */
    private function buildRecentMerges(int $mergesLimit): array
    {
        $publishingStudio = Workspace::query()->withoutGlobalScopes()
            ->where('status', WorkspaceStatusEnum::Published->value)
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->limit($mergesLimit)
            ->get();

        $result = [];

        foreach ($publishingStudio as $workspace) {
            $publishedAt = $workspace->published_at;
            $createdAt = $workspace->created_at;

            $durationOpenHours = 0;
            if ($publishedAt !== null && $createdAt !== null) {
                $durationOpenHours = (int) $createdAt->diffInHours($publishedAt);
            }

            $actorName = 'Unknown';
            if ($workspace->created_by !== null) {
                $creatorClass = config('auth.providers.users.model');
                if (is_string($creatorClass)) {
                    /** @var Model|null $creator */
                    $creator = $creatorClass::query()->find($workspace->created_by);
                    if ($creator !== null) {
                        $creatorName = $creator->getAttribute('name');
                        if (is_string($creatorName) && $creatorName !== '') {
                            $actorName = $creatorName;
                        }
                    }
                }
            }

            $pageCount = Page::query()->withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->count();

            $result[] = new WorkspaceMergeData(
                workspaceId: $workspace->id,
                name: $workspace->name,
                actorName: $actorName,
                pageCount: $pageCount,
                durationOpenHours: $durationOpenHours,
                publishedAt: $publishedAt !== null ? $publishedAt->toIso8601String() : '',
            );
        }

        return $result;
    }
}
