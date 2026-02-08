<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Sitemap;

use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Loader\TagLoader;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Models\Page;
use Capell\Core\Support\Sitemap\AbstractSitemapPages;
use Capell\Core\Support\Sitemap\SitemapChainBuilder;
use Illuminate\Support\Collection;

class TagsSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        $tagPage = TagLoader::getTagResultsPage($this->site, $this->language);

        if (! $tagPage instanceof Page) {
            return collect();
        }

        $tagChildren = $this->getTagPages($tagPage);

        $parent = $tagPage->parent;
        if ($parent === null) {
            return collect($tagChildren);
        }

        $node = SitemapChainBuilder::build($parent, $tagChildren);

        return collect([$node]);
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
        ]);
    }

    private function getTagPages(Page $tagPage): array
    {
        return TagLoader::getTags(site: $this->site, language: $this->language, limit: 100)
            ->map(fn (Tag $tag): array => $this->format($tagPage, $tag)->toArray())
            ->values()
            ->all();
    }
}
