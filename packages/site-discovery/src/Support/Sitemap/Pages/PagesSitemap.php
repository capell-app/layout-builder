<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Support\Sitemap\Pages;

use Aimeos\Nestedset\Collection as NestedsetCollection;
use Capell\Core\Enums\CacheEnum;
use Capell\Core\Models\Page;
use Capell\SiteDiscovery\Actions\DiscoverPublicPagesAction;
use Capell\SiteDiscovery\Data\DiscoverablePageData;
use Capell\SiteDiscovery\Data\SitemapPageData;
use Capell\SiteDiscovery\Support\Sitemap\AbstractSitemapPages;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use LogicException;

class PagesSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        throw_if($this->site->id === null, LogicException::class, 'Site ID is null in DefaultPages::fetch(). Ensure the Site model is persisted and loaded.');

        throw_if($this->language->id === null, LogicException::class, 'Language ID is null in DefaultPages::fetch(). Ensure the Language model is persisted and loaded.');

        $cacheKey = $this->cacheKey($this->site->id, $this->language->id);

        return Cache::remember($cacheKey, 3600, fn (): Collection => DiscoverPublicPagesAction::run($this->site, $this->language)
            ->map(fn (DiscoverablePageData $data): ?Page => $data->page)
            ->filter(fn (?Page $page): bool => $page instanceof Page)
            ->pipe(fn (Collection $pages): NestedsetCollection => new NestedsetCollection($pages->all()))
            ->pipe(fn (NestedsetCollection $pages): Collection => collect($pages->toTree()))
            ->map(fn (Page $page): SitemapPageData => $this->format($page)));
    }

    public function format(Page $page): SitemapPageData
    {
        return SitemapPageData::fromPage($page, withEditUrl: $this->withEditUrl);
    }

    private function cacheKey(int $siteId, int $languageId): string
    {
        return CacheEnum::sitemapPages($siteId, $languageId) . ($this->withEditUrl ? '.with-edit-urls' : '.public');
    }
}
