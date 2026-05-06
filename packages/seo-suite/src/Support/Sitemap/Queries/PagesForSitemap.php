<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\Sitemap\Queries;

use Capell\Core\Enums\TypeGroupEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Enums\RobotsDirectiveEnum;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PagesForSitemap
{
    /**
     * @return Collection<int, Page>
     */
    public function get(Site $site, Language $language): Collection
    {
        $query = Page::query();

        return $query->select([
            'pages.*',
            DB::raw("json_extract(pages.meta, '$.priority') AS meta_priority"),
        ])
            ->with(['translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id)])
            ->withWhereHas(
                'pageUrl',
                fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
            )
            ->withWhereHas(
                'type',
                fn (BuilderContract $query): BuilderContract => $query
                    ->where(
                        fn (Builder $query): Builder => $query->whereNull('group')
                            ->orWhereIn('group', config('capell.core.sitemap.type_groups', [TypeGroupEnum::Default->value])),
                    )
                    ->enabled()
                    ->visible()
                    ->accessible(),
            )
            ->where($query->qualifyColumn('site_id'), $site->id)
            ->where(
                fn (Builder $query): Builder => $query->whereNull('pages.meta')
                    ->orWhereJsonDoesntContain('pages.meta->hidden', true),
            )
            ->where(
                fn (Builder $query): Builder => $query->whereNull('pages.meta->robots')
                    ->orWhereJsonDoesntContain('pages.meta->robots', RobotsDirectiveEnum::NoIndex->value),
            )
            ->publishedDate()
            ->ordered()
            ->get()
            ->map(function (Page $page) use ($site): Page {
                $page->setRelation('site', $site);
                Page::setResolvedPageUrlSiteDomain($page, $site);

                return $page;
            });
    }
}
