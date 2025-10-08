<?php

declare(strict_types=1);

namespace Capell\Blog\Services\Loader;

use Capell\Core\Data\ArchiveMonthData;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Facades\CapellFrontend;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BlogLoader
{
    public static function getArchivePage(Site $site, Language $language): ?Page
    {
        $cacheKey = sprintf('site-%d-%d-archive-page', $site->id, $language->id);

        $fromCache = true;

        $page = CapellFrontend::cache($cacheKey, function () use ($site, $language, &$fromCache): ?Page {
            $fromCache = false;

            /** @var class-string<Page> $model */
            $model = CapellCore::getModel(ModelEnum::Page);

            return $model::getPageByType('archive', site: $site, language: $language);
        }) ?: null;

        if ($fromCache && $page) {
            event('eloquent.retrieved: ' . Page::class, $page);
        }

        return $page;
    }

    /**
     * @return Collection<ArchiveMonthData>
     */
    public static function getArchives(
        Site $site,
        Language $language,
        string $type,
        ?int $limit = null,
        bool $pagination = true,
        ?int $paginationPage = null,
        string $paginationKey = 'page',
    ): Collection {
        $cacheKey = sprintf('site-%d-%d-%s-%s-page-%s', $site->id, $language->id, $type, $limit, $paginationPage);

        return CapellFrontend::cache($cacheKey, function () use (
            $language,
            $limit,
            $paginationKey,
            $site,
            $type,
            $pagination,
        ): Collection {
            /* @var class-string<Models\Page> $model */
            $model = CapellCore::getModel(ModelEnum::Page);

            return $model::withoutEvents(fn () => $model::getPageArchivedDates(site: $site, language: $language, typeKey: $type)
                ->when(! $pagination, fn (Builder $query): Collection => $query->limit($limit)->get())
                ->when($pagination, fn (Builder $query): LengthAwarePaginator => $query->paginate($limit, ['*'], $paginationKey))
                ->map(fn ($record): ArchiveMonthData => new ArchiveMonthData(
                    year: $record->year,
                    month: $record->month,
                    total: $record->total,
                )));
        });
    }

    public static function getBlogPage(Site $site, string $type = 'blog'): ?Page
    {
        return Page::where('site_id', $site->id)
            ->whereRelation('type', 'key', $type)
            ->first();
    }
}
