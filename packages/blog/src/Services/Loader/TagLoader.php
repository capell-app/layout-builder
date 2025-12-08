<?php

declare(strict_types=1);

namespace Capell\Blog\Services\Loader;

use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Models\Tag;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Contracts\ModelServingInterface;
use Capell\Frontend\Enums\CacheEnum;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TagLoader
{
    public static function getPageTags(Page $page): Collection
    {
        $key = CacheEnum::pageTags($page->id);

        $fromCache = true;

        $tags = CapellCore::rememberCache($key, function () use ($page, &$fromCache): Collection {
            $fromCache = false;

            return $page->tags()->get();
        });

        if ($fromCache) {
            $tags->each(function (Tag $tag): void {
                app(ModelServingInterface::class)->track($tag);
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

            return $model::getFirstPageByTypeForSite('tag', site: $site, language: $language);
        }) ?: null;

        if ($fromCache && $page) {
            app(ModelServingInterface::class)->track($page);
        }

        return $page;
    }

    public static function getTags(
        Site $site,
        Language $language,
        ?int $limit,
        ?int $paginationPage = null,
        bool $withPagination = false,
        string $paginationKey = 'page',
    ): Collection|LengthAwarePaginator {
        if ($withPagination && ($limit === null || $limit === 0)) {
            $limit = config('capell-frontend.pagination_limit', 10);
        }

        $cacheKey = CacheEnum::siteTags($site->id, $language->id, $limit, $paginationPage);

        $fromCache = true;

        $tags = CapellCore::rememberCache($cacheKey, function () use (
            $language,
            $limit,
            $paginationKey,
            $site,
            $withPagination,
            &$fromCache
        ) {
            $fromCache = false;

            /* @var class-string<Tag> $model */
            $model = CapellCore::getModel(ModelEnum::Tag);

            return $model::query()
                ->withCount([
                    'pages' => fn (Builder $query) => $query->where('site_id', $site->id)
                        ->whereRelation('translation', 'language_id', $language->id),
                ])
                ->whereHas(
                    'pages',
                    fn (BuilderContract $query) => $query->where('site_id', $site->id)
                        ->whereRelation('translation', 'language_id', $language->id),
                )
                ->where('type', 'page')
                ->where(
                    fn (Builder $query) => $query->where('site_id', $site->id)->orWhereNull('site_id'),
                )
                ->tap(fn (Builder $query) => $query->whereNotNull($query->qualifyColumn('name->' . $language->code)))
                ->ordered()
                ->when(! $withPagination, fn (Builder $query) => $query->limit($limit)->get())
                ->when($withPagination, fn (Builder $query) => $query->paginate($limit, ['*'], $paginationKey));
        });

        if ($fromCache) {
            $tags->each(function (Tag $tag): void {
                app(ModelServingInterface::class)->track($tag);
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

            return $model::query()->where('type', 'page')
                ->where('slug->' . $language->code, $slug)
                ->first();
        }) ?: null;

        if ($fromCache && $tag) {
            app(ModelServingInterface::class)->track($tag);
        }

        return $tag;
    }
}
