<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Loader;

use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Enums\CacheEnum;
use Capell\Blog\Support\PageArchiveService;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Support\ModelServing\RetrievedModelStore;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BlogLoader
{
    public static function getArchivePage(Site $site, Language $language): ?Page
    {
        $cacheKey = CacheEnum::archivePage($site->id, $language->id);

        $fromCache = true;

        $page = CapellCore::rememberCache($cacheKey, function () use ($site, $language, &$fromCache): ?Page {
            $fromCache = false;

            /** @var class-string<Page> $model */
            $model = CapellCore::getModel(CoreModelEnum::Page);

            return $model::getFirstPageByTypeForSite(BlogPageTypeEnum::Archive->value, site: $site, language: $language);
        });

        if ($fromCache && $page instanceof Pageable) {
            resolve(RetrievedModelStore::class)->track($page);
        }

        return $page;
    }

    /**
     * @return Collection<ArchiveMonthData>
     */
    public static function getArchives(
        Site $site,
        Language $language,
        string $group,
        ?int $limit = null,
        bool $pagination = true,
        ?int $paginationPage = null,
        string $paginationKey = 'archives',
    ): Collection|LengthAwarePaginator {
        $cacheKey = CacheEnum::archives($site->id, $language->id, $group, $limit, $paginationPage);

        return CapellCore::rememberCache(
            $cacheKey,
            fn (): Collection|LengthAwarePaginator => resolve(PageArchiveService::class)->getArchivedCountsByMonth(
                site: $site,
                language: $language,
                group: $group,
                paginate: $pagination,
                perPage: $limit,
                paginationKey: $paginationKey,
            ),
        );
    }

    public static function getBlogPage(
        Site $site,
        string $type = BlogPageTypeEnum::Blog->value,
        ?Language $language = null,
    ): ?Page {
        $cacheKey = CacheEnum::blogPage($site->id, $language?->id ?? 'null', $type);

        $fromCache = true;

        $page = CapellCore::rememberCache($cacheKey, function () use ($site, $language, $type, &$fromCache): ?Page {
            $fromCache = false;

            /** @var class-string<Page> $model */
            $model = CapellCore::getModel(CoreModelEnum::Page);

            return $model::getFirstPageByTypeForSite($type, site: $site, language: $language);
        });

        if ($fromCache && $page instanceof Pageable) {
            resolve(RetrievedModelStore::class)->track($page);
        }

        return $page;
    }

    public static function getBlogPageUrl(Site $site, Language $language, bool $fullUrl = true): string
    {
        $page = self::getBlogPage($site, language: $language);

        return $fullUrl ? ($page?->pageUrl?->full_url ?? '') : ($page?->pageUrl?->url ?? '');
    }
}
