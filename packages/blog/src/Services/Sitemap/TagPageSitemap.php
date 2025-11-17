<?php

declare(strict_types=1);

namespace Capell\Blog\Services\Sitemap;

use Capell\Blog\Models\Tag;
use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\CapellCoreHelper;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Enums\CacheEnum;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Services\Sitemap\AbstractSitemapPages;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TagPageSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        $cacheKey = CacheEnum::sitemapTagPages($this->site->id, $this->language->id);

        return Cache::remember($cacheKey, 3600, function (): Collection {
            $tagPage = CapellCoreHelper::getFirstPageByTypeForSite(
                'tag',
                $this->site,
                $this->language,
                fn ($query) => $query->withWhereHas(
                    'parent',
                    fn (BuilderContract $query) => $query->with([
                        'pageUrl' => fn ($query) => $query->with('siteDomain')->where('language_id', $this->language->id),
                        'translation' => fn ($query) => $query->where('language_id', $this->language->id),
                    ]),
                ),
            );

            if (! $tagPage instanceof Page) {
                return collect([]);
            }

            $tagsPage = $tagPage->parent;

            return collect([
                SitemapPageData::from([
                    'label' => $tagsPage->translation->title,
                    'url' => $tagsPage->pageUrl->full_url,
                    'children' => $this->getTagPages($tagPage),
                    'editUrl' => $this->withEditUrl ? GetEditPageResourceUrlAction::run($tagsPage) : null,
                ])
                    ->toArray(),
            ]);
        });
    }

    public function format(Page $tagPage, Tag $tag): SitemapPageData
    {
        $url = $tagPage->pageUrl->url;

        if (str_ends_with($url, '/*')) {
            $url = mb_substr($url, 0, -2);
        }

        $url .= '/' . $tag->getTranslation('slug', $this->language->code);

        return SitemapPageData::from([
            'label' => $tag->getTranslation('name', $this->language->code) . ' (' . $tag->pages_count . ')',
            'url' => $url,
            'editUrl' => $this->withEditUrl ? GetEditPageResourceUrlAction::run($tagPage) : null,
        ]);
    }

    private function getTagPages(Page $tagPage): Collection
    {
        /** @var class-string<Page> $model */
        $model = CapellCore::getModel(ModelEnum::Page);

        return $model::getTags(site: $this->site, language: $this->language, with_page_count: true)
            ->limit(100)
            ->get()
            ->map(fn (Tag $tag): array => $this->format($tagPage, $tag)->toArray());
    }
}
