<?php

declare(strict_types=1);

namespace Capell\Blog\Services\Loader;

use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Services\PageArchiveService;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Contracts\ModelServingInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BlogLoader
{
    public static function getArchivePage(Site $site, Language $language): ?Page
    {
        $cacheKey = sprintf('site-%d-%d-archive-page', $site->id, $language->id);

        $fromCache = true;

        $page = CapellCore::rememberCache($cacheKey, function () use ($site, $language, &$fromCache): ?Page {
            $fromCache = false;

            /** @var class-string<Page> $model */
            $model = CapellCore::getModel(ModelEnum::Page);

            return $model::getFirstPageByTypeForSite(BlogPageTypeEnum::Archive->value, site: $site, language: $language);
        }) ?: null;

        if ($fromCache && $page) {
            resolve(ModelServingInterface::class)->track($page);
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
    ): Collection|LengthAwarePaginator {
        $cacheKey = sprintf('site-%d-%d-%s-%s-page-%s', $site->id, $language->id, $type, $limit, $paginationPage);

        return CapellCore::rememberCache(
            $cacheKey,
            fn (): Collection|LengthAwarePaginator => app(PageArchiveService::class)->getArchivedCountsByMonth(
                site: $site,
                language: $language,
                typeKey: $type,
                paginate: $pagination,
                perPage: $limit,
                paginationKey: $paginationKey,
            ),
        );
    }

    public static function getBlogPage(Site $site, string $type = BlogPageTypeEnum::Blog->value): ?Page
    {
        return Page::query()->where('site_id', $site->id)
            ->whereRelation('type', 'key', $type)
            ->first();
    }
}
