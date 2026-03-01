<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Sitemap;

use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Loader\TagLoader;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Models\Page;
use Capell\Core\Support\Sitemap\AbstractSitemapPages;
use Capell\Core\Support\Sitemap\SitemapChainBuilder;
use Exception;
use Illuminate\Support\Collection;

class TagsSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        $tagPage = TagLoader::getTagResultsPage($this->site, $this->language);

        throw_unless($tagPage instanceof Page, Exception::class, 'Tag results page not found for the current site and language.');

        $tagChildren = $this->getTagPages($tagPage);

        $node = SitemapChainBuilder::build($tagPage->parent, children: $tagChildren);

        return collect([$node]);
    }

    public function format(Page $tagPage, Tag $tag): SitemapPageData
    {
        $url = $tagPage->pageUrl->full_url;

        if (str_ends_with($url, '/*')) {
            $url = mb_substr($url, 0, -2);
        }

        $url .= '/' . $tag->getTranslation('slug', $this->language->code);

        return SitemapPageData::from([
            'label' => $tag->getTranslation('name', $this->language->code) . ' (' . $tag->pages_count . ')',
            'url' => $url,
            'page_id' => $tag->id,
        ]);
    }

    private function getTagPages(Page $tagPage): array
    {
        return TagLoader::getTags(site: $this->site, language: $this->language, limit: 100)
            ->map(fn (Tag $tag): SitemapPageData => $this->format($tagPage, $tag))
            ->values()
            ->all();
    }
}
