<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Sitemap;

use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Models\Article;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Sitemap\AbstractSitemapPages;
use Capell\Frontend\Support\Loader\PageLoader;
use Exception;
use Illuminate\Support\Collection;

class ArticlesSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        // Locate the Blog page for the site & language
        $blogPage = BlogLoader::getBlogPage($this->site);
        if (! $blogPage instanceof Pageable) {
            throw new Exception('Blog page not found for site: ' . $this->site->name);
        }

        // Build recursive node: blog page with articles children
        $node = SitemapPageData::fromPage($blogPage, withEditUrl: $this->withEditUrl);

        $articles = PageLoader::getPages(
            language: $this->language,
            site: $this->site,
            limit: 100,
            ordering: PageOrderEnum::Latest,
            pageGroup: BlogTypeGroupEnum::Article->value,
            morphModel: CapellCore::getModel(ModelEnum::Article),
        );

        $node->children = $articles->map(
            fn (Article $child): SitemapPageData => SitemapPageData::fromPage($child, withEditUrl: $this->withEditUrl),
        )
            ->values();

        return collect([$node]);
    }
}
