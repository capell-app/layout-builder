<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Sitemap;

use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Support\Loader\TagLoader;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Page;
use Capell\SeoTools\Data\SitemapPageData;
use Capell\SeoTools\Support\Sitemap\AbstractSitemapPages;
use Capell\SeoTools\Support\Sitemap\SitemapChainBuilder;
use Capell\Tags\Filament\Resources\Tags\TagResource;
use Capell\Tags\Models\Tag;
use Illuminate\Support\Collection;

class TagsSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        $tagPage = Page::getFirstPageByTypeForSite(BlogPageTypeEnum::Tag->value, site: $this->site, language: $this->language);

        if (! $tagPage instanceof Pageable) {
            return collect([]);
        }

        $tagPage->loadMissing([
            'parent.translation',
            'parent.pageUrl.siteDomain',
            'parent.parent.translation',
            'parent.parent.pageUrl.siteDomain',
        ]);

        $page = $tagPage;
        while ($page instanceof Page) {
            $page->pageUrl?->setRelation('siteDomain', $this->domain);
            $page->pageUrl?->setRelation('language', $this->language);
            $page = $page->parent;
        }

        $tagChildren = $this->getTagPages($tagPage);

        $parent = $tagPage->parent;
        if ($parent === null) {
            return $tagChildren;
        }

        $node = SitemapChainBuilder::build($parent, children: $tagChildren, withEditUrl: $this->withEditUrl);

        return collect([$node]);
    }

    public function format(Page $tagPage, Tag $tag): SitemapPageData
    {
        return new SitemapPageData(
            label: $tag->getTranslation('name', $this->language->code) . ' (' . $tag->taggables_count . ')',
            url: $tag->getUrl($tagPage, $this->language),
            editUrl: $this->withEditUrl ? TagResource::getUrl('edit', ['record' => $tag]) : null,
            pageableType: $tag->getMorphClass(),
            pageId: $tag->id,
        );
    }

    private function getTagPages(Page $tagPage): Collection
    {
        return TagLoader::getTags(site: $this->site, language: $this->language, limit: 100)
            ->map(fn (Tag $tag): SitemapPageData => $this->format($tagPage, $tag))
            ->values();
    }
}
