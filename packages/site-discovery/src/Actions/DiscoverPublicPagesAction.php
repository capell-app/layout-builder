<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Actions;

use Capell\Core\Enums\TypeGroupEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SiteDiscovery\Data\DiscoverablePageData;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static Collection<int, DiscoverablePageData> run(Site $site, Language $language)
 */
final class DiscoverPublicPagesAction
{
    use AsAction;

    /**
     * @return Collection<int, DiscoverablePageData>
     */
    public function handle(Site $site, Language $language): Collection
    {
        $query = Page::query();

        return $query->select([
            'pages.*',
            DB::raw("json_extract(pages.meta, '$.priority') AS meta_priority"),
        ])
            ->with(['translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id)])
            ->withWhereHas(
                'pageUrl',
                fn (BuilderContract $query): BuilderContract => $query
                    ->where('language_id', $language->id)
                    ->where('status', true)
                    ->whereNull('type'),
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
                    ->orWhereJsonDoesntContain('pages.meta->robots', 'noindex'),
            )
            ->publishedDate()
            ->ordered()
            ->get()
            ->map(function (Page $page) use ($site): DiscoverablePageData {
                $page->setRelation('site', $site);
                Page::setResolvedPageUrlSiteDomain($page, $site);

                return new DiscoverablePageData(
                    pageId: (int) $page->getKey(),
                    title: trim(strip_tags($page->translation?->title ?? $page->translation?->label ?? $page->name ?? '')),
                    url: $page->pageUrl?->full_url ?? '',
                    lastModified: $page->updated_at,
                    priority: is_numeric($page->meta['priority'] ?? null) ? (float) $page->meta['priority'] : null,
                    changeFrequency: is_string($page->meta['changefreq'] ?? null) ? $page->meta['changefreq'] : null,
                    page: $page,
                );
            })
            ->filter(fn (DiscoverablePageData $page): bool => $page->url !== '')
            ->values();
    }
}
