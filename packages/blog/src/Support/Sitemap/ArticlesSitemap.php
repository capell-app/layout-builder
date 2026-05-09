<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Sitemap;

use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Models\Article;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Page;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\SiteDiscovery\Data\SitemapPageData;
use Capell\SiteDiscovery\Support\Sitemap\AbstractSitemapPages;
use Illuminate\Support\Collection;

class ArticlesSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        // Locate the Blog page for the site & language
        $blogPage = Page::getFirstPageByTypeForSite('blog', site: $this->site, language: $this->language);
        if (! $blogPage instanceof Pageable) {
            return collect();
        }

        // Build recursive node: blog page with articles children
        $node = SitemapPageData::fromPage($blogPage, withEditUrl: $this->withEditUrl);

        $articles = PageLoader::getPages(
            language: $this->language,
            site: $this->site,
            limit: 100,
            ordering: PageOrderEnum::Latest,
            pageGroup: BlogTypeGroupEnum::Article->value,
            morphModel: 'article',
        );

        $node->children = $articles->map(
            fn (Article $child): SitemapPageData => SitemapPageData::fromPage($child, withEditUrl: $this->withEditUrl),
        )
            ->values();

        return collect([$node]);
    }
}
