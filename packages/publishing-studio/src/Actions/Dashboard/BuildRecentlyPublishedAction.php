<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions\Dashboard;

use Capell\Admin\Data\Dashboard\RecentlyPublishedData;
use Capell\Admin\Data\Dashboard\RecentlyPublishedItemData;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelData\DataCollection;

final class BuildRecentlyPublishedAction
{
    use AsAction;

    public function handle(int $limit = 10, ?Site $site = null): RecentlyPublishedData
    {
        $publishingStudio = Workspace::query()
            ->where('status', WorkspaceStatusEnum::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->get();

        if ($publishingStudio->isEmpty()) {
            return new RecentlyPublishedData(
                items: RecentlyPublishedItemData::collect([], DataCollection::class),
            );
        }

        $workspaceIds = $publishingStudio->pluck('id');

        $pageQuery = Page::query()
            ->withoutGlobalScopes()
            ->with('site')
            ->whereIn('workspace_id', $workspaceIds)
            ->latest('updated_at');

        if ($site instanceof Site) {
            $pageQuery->where('site_id', $site->id);
        }

        $pages = $pageQuery->get();

        // Index publishing-studio by id for fast lookup when building items.
        $workspaceIndex = $publishingStudio->keyBy('id');

        /** @var Collection<int, RecentlyPublishedItemData> $items */
        $items = $pages
            ->map(function (Page $page) use ($workspaceIndex): ?RecentlyPublishedItemData {
                /** @var Workspace|null $workspace */
                $workspace = $workspaceIndex->get($page->workspace_id);

                if ($workspace === null) {
                    return null;
                }

                $siteName = $page->relationLoaded('site') && $page->site instanceof Site
                    ? $page->site->name
                    : '';

                return new RecentlyPublishedItemData(
                    pageId: $page->id,
                    title: $page->name,
                    siteName: $siteName,
                    publishedAt: $workspace->published_at?->toIso8601String(),
                    editUrl: PageResource::getUrl('edit', ['record' => $page]),
                );
            })
            ->filter()
            ->values()
            ->sortByDesc(fn (RecentlyPublishedItemData $item): string => $item->publishedAt ?? '')
            ->take($limit)
            ->values();

        return new RecentlyPublishedData(
            items: RecentlyPublishedItemData::collect($items->all(), DataCollection::class),
        );
    }
}
