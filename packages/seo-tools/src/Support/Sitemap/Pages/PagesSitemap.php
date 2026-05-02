<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\Sitemap\Pages;

use Capell\Core\Enums\CacheEnum;
use Capell\Core\Models\Page;
use Capell\SeoTools\Data\SitemapPageData;
use Capell\SeoTools\Support\Sitemap\AbstractSitemapPages;
use Capell\SeoTools\Support\Sitemap\Queries\PagesForSitemap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use LogicException;

class PagesSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        throw_if($this->site->id === null, LogicException::class, 'Site ID is null in DefaultPages::fetch(). Ensure the Site model is persisted and loaded.');

        throw_if($this->language->id === null, LogicException::class, 'Language ID is null in DefaultPages::fetch(). Ensure the Language model is persisted and loaded.');

        $cacheKey = CacheEnum::sitemapPages($this->site->id, $this->language->id);

        return Cache::remember($cacheKey, 3600, fn (): Collection => resolve(PagesForSitemap::class)
            ->get($this->site, $this->language)
            ->toTree()
            ->map(fn (Page $page): SitemapPageData => $this->format($page)));
    }

    public function format(Page $page): SitemapPageData
    {
        return SitemapPageData::fromPage($page, withEditUrl: $this->withEditUrl);
    }
}
