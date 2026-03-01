<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Sitemap;

use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Models\Page;
use Capell\Core\Support\Sitemap\AbstractSitemapPages;
use Exception;
use Illuminate\Support\Collection;

class ArticlesSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        // Locate the Blog page for the site & language
        $blogPage = BlogLoader::getBlogPage($this->site);
        if (! $blogPage instanceof Page) {
            throw new Exception('Blog page not found for site: ' . $this->site->name);
        }

        // Build recursive node: blog page with article children
        $node = $this->formatRecursive($blogPage);

        return collect([$node]);
    }

    private function formatRecursive(Page $page): SitemapPageData
    {
        $data = SitemapPageData::fromPage($page, withEditUrl: $this->withEditUrl);

        $children = $page->children
            ->filter(
                fn (Page $child): bool => $child->type->group === BlogTypeGroupEnum::Article->value,
            )
            ->map(fn (Page $child): array => $this->formatRecursive($child)->all())
            ->values()
            ->all();

        // Attach children on the data object
        $data->children = collect(array_map(SitemapPageData::from(...), $children));

        return $data;
    }
}
