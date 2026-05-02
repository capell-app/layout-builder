<?php

declare(strict_types=1);

namespace Capell\Workspaces\Actions\Dashboard;

use Capell\Admin\Data\Dashboard\MyWorkItemData;
use Capell\Admin\Data\Dashboard\MyWorkQueueData;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Models\Page;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Models\WorkspaceReviewAssignment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelData\DataCollection;

final class BuildMyWorkQueueAction
{
    use AsAction;

    public function handle(Authenticatable $user, int $limit = 15, int $scheduledDays = 7): MyWorkQueueData
    {
        $userId = $user->getAuthIdentifier();

        $draftItems = $this->buildDraftItems($userId);
        $approvalItems = $this->buildApprovalItems($user);
        $scheduledItems = $this->buildScheduledItems($userId, $scheduledDays);

        /** @var Collection<int, MyWorkItemData> $merged */
        $merged = $draftItems
            ->merge($approvalItems)
            ->merge($scheduledItems)
            ->take($limit)
            ->values();

        return new MyWorkQueueData(
            items: MyWorkItemData::collect($merged->all(), DataCollection::class),
        );
    }

    /**
     * Pages in open workspaces owned by this user (not yet submitted/merged).
     *
     * @return Collection<int, MyWorkItemData>
     */
    private function buildDraftItems(int|string $userId): Collection
    {
        $workspaceIds = Workspace::query()
            ->whereIn('status', [
                WorkspaceStatusEnum::Open->value,
                WorkspaceStatusEnum::InReview->value,
            ])
            ->where('created_by', $userId)
            ->pluck('id');

        if ($workspaceIds->isEmpty()) {
            return collect();
        }

        return Page::query()
            ->withoutGlobalScopes()
            ->whereIn('workspace_id', $workspaceIds)
            ->latest('updated_at')
            ->get()
            ->map(fn (Page $page): MyWorkItemData => new MyWorkItemData(
                pageId: $page->id,
                title: $page->name,
                kind: 'draft',
                editUrl: PageResource::getUrl('edit', ['record' => $page]),
                scheduledAt: null,
                updatedAt: $page->updated_at?->toIso8601String(),
            ));
    }

    /**
     * Pages in workspaces where this user has an undecided review assignment.
     *
     * @return Collection<int, MyWorkItemData>
     */
    private function buildApprovalItems(Authenticatable $user): Collection
    {
        $morphClass = method_exists($user, 'getMorphClass') ? $user->getMorphClass() : $user::class;

        $workspaceIds = WorkspaceReviewAssignment::query()
            ->where('reviewer_type', $morphClass)
            ->where('reviewer_id', $user->getAuthIdentifier())
            ->whereNull('decision')
            ->pluck('workspace_id')
            ->unique();

        if ($workspaceIds->isEmpty()) {
            return collect();
        }

        return Page::query()
            ->withoutGlobalScopes()
            ->whereIn('workspace_id', $workspaceIds)
            ->latest('updated_at')
            ->get()
            ->map(fn (Page $page): MyWorkItemData => new MyWorkItemData(
                pageId: $page->id,
                title: $page->name,
                kind: 'awaiting_approval',
                editUrl: PageResource::getUrl('edit', ['record' => $page]),
                scheduledAt: null,
                updatedAt: $page->updated_at?->toIso8601String(),
            ));
    }

    /**
     * Pages in scheduled workspaces owned by this user with publish_at within $scheduledDays.
     *
     * @return Collection<int, MyWorkItemData>
     */
    private function buildScheduledItems(int|string $userId, int $scheduledDays): Collection
    {
        $cutoff = now()->addDays($scheduledDays);

        $workspaces = Workspace::query()->withoutGlobalScopes()
            ->where('status', WorkspaceStatusEnum::Scheduled->value)
            ->where('created_by', $userId)
            ->whereNotNull('publish_at')
            ->where('publish_at', '>', now())
            ->where('publish_at', '<=', $cutoff)
            ->get();

        if ($workspaces->isEmpty()) {
            return collect();
        }

        $result = collect();

        foreach ($workspaces as $workspace) {
            $publishAt = $workspace->publish_at;
            $publishAtString = $publishAt?->toIso8601String();

            $pages = Page::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->latest('updated_at')
                ->get();

            foreach ($pages as $page) {
                $result->push(new MyWorkItemData(
                    pageId: $page->id,
                    title: $page->name,
                    kind: 'scheduled',
                    editUrl: PageResource::getUrl('edit', ['record' => $page]),
                    scheduledAt: $publishAtString,
                    updatedAt: $page->updated_at?->toIso8601String(),
                ));
            }
        }

        return $result;
    }
}
