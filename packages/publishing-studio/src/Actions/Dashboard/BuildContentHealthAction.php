<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions\Dashboard;

use Capell\Admin\Data\Dashboard\ContentHealthData;
use Capell\Admin\Data\Dashboard\ContentHealthIssueData;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Enums\TranslatableType;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelData\DataCollection;

final class BuildContentHealthAction
{
    use AsAction;

    public function handle(
        ?Site $site = null,
        ?Language $language = null,
        int $staleDays = 90,
    ): ContentHealthData {
        $publishedWorkspaceIds = $this->resolvePublishedWorkspaceIds();

        $issues = [
            $this->buildMissingMetaIssue($publishedWorkspaceIds, $site),
            $this->buildDuplicateTitlesIssue($publishedWorkspaceIds, $site),
            $this->buildEmptyContentIssue($publishedWorkspaceIds, $site, $language),
            $this->buildStaleIssue($publishedWorkspaceIds, $site, $staleDays),
        ];

        return new ContentHealthData(
            issues: ContentHealthIssueData::collect($issues, DataCollection::class),
        );
    }

    /**
     * @return Collection<int, int>
     */
    private function resolvePublishedWorkspaceIds(): Collection
    {
        return Workspace::query()
            ->where('status', WorkspaceStatusEnum::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->pluck('id');
    }

    /**
     * Build the base published-pages query, optionally scoped to a site.
     *
     * @param  Collection<int, int>  $publishedWorkspaceIds
     * @return Builder<Page>
     */
    private function publishedPageQuery(Collection $publishedWorkspaceIds, ?Site $site): Builder
    {
        $query = Page::query()
            ->withoutGlobalScopes()
            ->whereIn('workspace_id', $publishedWorkspaceIds);

        if ($site instanceof Site) {
            $query->where('site_id', $site->id);
        }

        return $query;
    }

    /**
     * @param  Collection<int, int>  $publishedWorkspaceIds
     */
    private function buildMissingMetaIssue(Collection $publishedWorkspaceIds, ?Site $site): ContentHealthIssueData
    {
        if ($publishedWorkspaceIds->isEmpty()) {
            return $this->makeIssue('missing_meta', 'Missing meta description', 0);
        }

        $pageIds = $this->publishedPageQuery($publishedWorkspaceIds, $site)->pluck('id');

        if ($pageIds->isEmpty()) {
            return $this->makeIssue('missing_meta', 'Missing meta description', 0);
        }

        // Load translations for published pages and check meta['description'] in PHP —
        // this avoids JSON_EXTRACT which is MySQL-only and fails in SQLite test environments.
        $missingCount = Translation::query()
            ->where('translatable_type', TranslatableType::Page->value)
            ->whereIn('translatable_id', $pageIds)
            ->get()
            ->groupBy('translatable_id')
            ->filter(fn (Collection $translations): bool => $translations->every(function (Translation $translation): bool {
                $meta = (array) $translation->meta;
                $description = $meta['description'] ?? null;

                return $description === null || $description === '';
            }))
            ->count();

        return $this->makeIssue('missing_meta', 'Missing meta description', $missingCount);
    }

    /**
     * @param  Collection<int, int>  $publishedWorkspaceIds
     */
    private function buildDuplicateTitlesIssue(Collection $publishedWorkspaceIds, ?Site $site): ContentHealthIssueData
    {
        if ($publishedWorkspaceIds->isEmpty()) {
            return $this->makeIssue('duplicate_titles', 'Duplicate page titles', 0);
        }

        $pages = $this->publishedPageQuery($publishedWorkspaceIds, $site)
            ->select(['id', 'site_id'])
            ->get();

        if ($pages->isEmpty()) {
            return $this->makeIssue('duplicate_titles', 'Duplicate page titles', 0);
        }

        $pageIds = $pages->pluck('id');

        // Load all translations for published pages to find duplicates per site.
        $translations = Translation::query()
            ->where('translatable_type', TranslatableType::Page->value)
            ->whereIn('translatable_id', $pageIds)
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->get(['translatable_id', 'title']);

        // Build a map of page_id => site_id for quick lookup.
        $siteIdByPageId = $pages->pluck('site_id', 'id');

        // Group by site_id + title; any group with >1 page has duplicates.
        $grouped = $translations->groupBy(function (Translation $translation) use ($siteIdByPageId): string {
            $siteId = $siteIdByPageId->get($translation->translatable_id, 0);

            return $siteId . '::' . $translation->title;
        });

        $duplicatePageIds = $grouped
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->flatMap(fn (Collection $group): Collection => $group->pluck('translatable_id'))
            ->unique()
            ->count();

        return $this->makeIssue('duplicate_titles', 'Duplicate page titles', $duplicatePageIds);
    }

    /**
     * @param  Collection<int, int>  $publishedWorkspaceIds
     */
    private function buildEmptyContentIssue(
        Collection $publishedWorkspaceIds,
        ?Site $site,
        ?Language $language,
    ): ContentHealthIssueData {
        if (! $language instanceof Language || $publishedWorkspaceIds->isEmpty()) {
            return $this->makeIssue('empty_content', 'Empty content', 0);
        }

        $pageIds = $this->publishedPageQuery($publishedWorkspaceIds, $site)->pluck('id');

        if ($pageIds->isEmpty()) {
            return $this->makeIssue('empty_content', 'Empty content', 0);
        }

        // Find pages that have a translation for this language but the content is empty.
        $emptyCount = Translation::query()
            ->where('translatable_type', TranslatableType::Page->value)
            ->whereIn('translatable_id', $pageIds)
            ->where('language_id', $language->id)
            ->where(function (Builder $query): void {
                $query->whereNull('content')
                    ->orWhere('content', '');
            })
            ->count();

        return $this->makeIssue('empty_content', 'Empty content', $emptyCount);
    }

    /**
     * @param  Collection<int, int>  $publishedWorkspaceIds
     */
    private function buildStaleIssue(
        Collection $publishedWorkspaceIds,
        ?Site $site,
        int $staleDays,
    ): ContentHealthIssueData {
        if ($publishedWorkspaceIds->isEmpty()) {
            return $this->makeIssue('stale', 'Stale pages (not updated in ' . $staleDays . ' days)', 0);
        }

        $threshold = now()->subDays($staleDays);

        $count = $this->publishedPageQuery($publishedWorkspaceIds, $site)
            ->where('updated_at', '<', $threshold)
            ->count();

        return $this->makeIssue('stale', 'Stale pages (not updated in ' . $staleDays . ' days)', $count);
    }

    private function makeIssue(string $id, string $label, int $count): ContentHealthIssueData
    {
        return new ContentHealthIssueData(
            id: $id,
            label: $label,
            count: $count,
            filterUrl: PageResource::getUrl('index'),
        );
    }
}
