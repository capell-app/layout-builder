<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Loader;

use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Enums\CacheEnum;
use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Enums\TagTypeEnum;
use Capell\Blog\Models\Tag;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Support\ModelServing\RetrievedModelStore;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TagLoader
{
    public static function getPageTags(Pageable $page): Collection
    {
        $key = CacheEnum::pageTags($page->id);

        $fromCache = true;

        $tags = CapellCore::rememberCache($key, function () use ($page, &$fromCache): Collection {
            $fromCache = false;

            return $page->tags()->ordered()->get();
        });

        if ($fromCache) {
            $tags->each(function (Tag $tag): void {
                resolve(RetrievedModelStore::class)->track($tag);
            });
        }

        return $tags;
    }

    public static function getTagResultsPage(Site $site, Language $language): ?Page
    {
        $cacheKey = CacheEnum::tagResultsPage($site->id, $language->id);

        $fromCache = true;

        $page = CapellCore::rememberCache($cacheKey, function () use ($site, $language, &$fromCache): ?Page {
            $fromCache = false;

            /** @var class-string<Page> $model */
            $model = CapellCore::getModel(CoreModelEnum::Page);

            return $model::getFirstPageByTypeForSite(BlogPageTypeEnum::Tag->value, site: $site, language: $language);
        });

        if ($fromCache && $page instanceof Pageable) {
            resolve(RetrievedModelStore::class)->track($page);
        }

        return $page;
    }

    /**
     * Returns a query builder for tags for use in chunked/large operations.
     */
    public static function getTagsQuery(
        Site $site,
        Language $language,
        bool $hasArticles = false,
    ): Builder {
        /* @var class-string<Tag> $model */
        $model = CapellCore::getModel(ModelEnum::Tag);

        return $model::query()
            ->withCount([
                'taggables' => fn (Builder $query): Builder => $query->whereHas(
                    'taggable',
                    fn (Builder $query): Builder => $query->where('site_id', $site->id)
                        ->whereRelation('translation', 'language_id', $language->id),
                ),
            ])
            ->where('type', TagTypeEnum::Page)
            ->where(
                fn (Builder $query): Builder => $query->where('site_id', $site->id)->orWhereNull('site_id'),
            )
            ->when(
                $hasArticles,
                fn (Builder $query) => $query->whereHas(
                    'taggables',
                    fn (BuilderContract $query): BuilderContract => $query->whereHas(
                        'taggable',
                        fn (BuilderContract $query): BuilderContract => $query->where('site_id', $site->id)
                            ->whereRelation('translation', 'language_id', $language->id),
                    ),
                ),
            )
            ->tap(fn (Builder $query) => $query->whereNotNull($query->qualifyColumn('name->' . $language->code)))
            ->ordered();
    }

    /**
     * Returns a collection or paginator of tags (cached, for UI use).
     */
    public static function getTags(
        Site $site,
        Language $language,
        ?int $limit = null,
        bool $hasArticles = false,
        ?int $paginationPage = null,
        bool $withPagination = false,
        string $paginationKey = 'page',
    ): Collection|LengthAwarePaginator {
        if ($withPagination && ($limit === null || $limit === 0)) {
            $limit = config('capell-frontend.pagination_limit', 10);
        }

        $cacheKey = CacheEnum::siteTags($site->id, $language->id, $hasArticles, $limit, $paginationPage);

        $fromCache = true;

        $tags = CapellCore::rememberCache($cacheKey, function () use (
            $language,
            $hasArticles,
            $limit,
            $paginationKey,
            $site,
            $withPagination,
            &$fromCache
        ) {
            $fromCache = false;
            $query = self::getTagsQuery($site, $language, $hasArticles);
            if ($withPagination) {
                return $query->paginate($limit, ['*'], $paginationKey);
            }

            if ($limit) {
                $query->limit($limit);
            }

            return $query->get();
        });

        if ($fromCache && $tags instanceof Collection) {
            $tags->each(function (Tag $tag): void {
                resolve(RetrievedModelStore::class)->track($tag);
            });
        }

        return $tags;
    }

    public static function tagPage(string $slug, Language $language): ?Tag
    {
        $key = CacheEnum::tagPage($slug);

        $fromCache = true;

        $tag = CapellCore::rememberCache($key, function () use ($slug, $language, &$fromCache): ?Tag {
            $fromCache = false;

            /** @var class-string<Tag> $model */
            $model = CapellCore::getModel(ModelEnum::Tag);

            return $model::query()->where('type', TagTypeEnum::Page)
                ->where('slug->' . $language->code, $slug)
                ->first();
        });

        if ($fromCache && $tag instanceof Tag) {
            resolve(RetrievedModelStore::class)->track($tag);
        }

        return $tag;
    }
}
