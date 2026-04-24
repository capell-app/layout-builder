<?php

declare(strict_types=1);

namespace Capell\Workspaces\Actions\Dashboard;

use Capell\Core\Models\Page;
use Capell\Workspaces\Data\Dashboard\MergeHistoryEntryData;
use Capell\Workspaces\Data\Dashboard\WorkspaceMergeHistoryData;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelData\DataCollection;

/**
 * @method static WorkspaceMergeHistoryData run(int $limit = 30)
 */
final class BuildWorkspaceMergeHistoryAction
{
    use AsAction;

    public function handle(int $limit = 30): WorkspaceMergeHistoryData
    {
        $workspaces = Workspace::query()->withoutGlobalScopes()
            ->where('status', WorkspaceStatusEnum::Published->value)
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->limit($limit)
            ->get();

        $creatorIds = $workspaces
            ->filter(fn (Workspace $workspace): bool => $workspace->created_by !== null)
            ->pluck('created_by')
            ->unique();

        $creators = [];
        if ($creatorIds->isNotEmpty()) {
            $creatorClass = config('auth.providers.users.model');
            if (is_string($creatorClass)) {
                /** @var array<int, Model> $results */
                $results = $creatorClass::query()
                    ->whereIn('id', $creatorIds)
                    ->get()
                    ->keyBy('id');

                /** @var Model $creator */
                foreach ($results as $creator) {
                    $creatorName = $creator->getAttribute('name');
                    if (is_string($creatorName) && $creatorName !== '') {
                        $creators[$creator->getAttribute('id')] = $creatorName;
                    }
                }
            }
        }

        $entries = [];

        foreach ($workspaces as $workspace) {
            $publishedAt = $workspace->published_at;
            $createdAt = $workspace->created_at;

            $durationOpenHours = 0;
            if ($publishedAt !== null && $createdAt !== null) {
                $durationOpenHours = (int) $createdAt->diffInHours($publishedAt);
            }

            $actorName = 'Unknown';
            if ($workspace->created_by !== null && isset($creators[$workspace->created_by])) {
                $actorName = $creators[$workspace->created_by];
            }

            $pageCount = Page::query()
                ->withoutWorkspaceScope()
                ->where('workspace_id', $workspace->id)
                ->count();

            $entries[] = new MergeHistoryEntryData(
                workspaceId: $workspace->id,
                name: $workspace->name,
                actorName: $actorName,
                pageCount: $pageCount,
                durationOpenHours: $durationOpenHours,
                publishedAt: $publishedAt !== null ? $publishedAt->toIso8601String() : '',
            );
        }

        return new WorkspaceMergeHistoryData(
            entries: MergeHistoryEntryData::collect($entries, DataCollection::class),
        );
    }
}
