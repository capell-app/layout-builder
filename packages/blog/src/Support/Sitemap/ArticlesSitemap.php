<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Sitemap;

use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Models\Page;
use Capell\Core\Support\Sitemap\AbstractSitemapPages;
use Illuminate\Support\Collection;

class ArticlesSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        // Locate the Blog page for the site & language
        $blogPage = BlogLoader::getBlogPage($this->site);
        if (! $blogPage instanceof Page) {
            return collect();
        }

        // Build recursive node: blog page with article children
        $node = $this->formatRecursive($blogPage)->toArray();

        return collect([$node]);
    }

    private function formatRecursive(Page $page): SitemapPageData
    {
        // Base node for current page
        $data = SitemapPageData::fromPage($page, withEditUrl: $this->withEditUrl);

        // Collect Article-group children recursively
        $children = $page->children
            ->filter(function (Page $child): bool {
                // Only include pages in the Article group
                return ($child->type_group ?? null) === BlogTypeGroupEnum::Article->value;
            })
            ->map(fn (Page $child): array => $this->formatRecursive($child)->toArray())
            ->values()
            ->all();

        // Attach children on the data object
        $data->children = collect(array_map(static fn (array $item) => SitemapPageData::from($item), $children));

        return $data;
    }
}
