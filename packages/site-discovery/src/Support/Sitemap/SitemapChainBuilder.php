<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Support\Sitemap;

use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Exceptions\UrlMissingSiteDomainException;
use Capell\Core\Models\Page;
use Capell\SiteDiscovery\Data\SitemapPageData;
use Illuminate\Support\Collection;

final class SitemapChainBuilder
{
    /**
     * Build the ancestor chain starting at the given parent, attaching provided children.
     * Returns the top-level node as a SitemapPageData object.
     *
     * @param  null|Collection<int, SitemapPageData>  $children
     */
    public static function build(Page $page, ?Collection $children = null, bool $withEditUrl = false): SitemapPageData
    {
        $node = new SitemapPageData(
            label: $page->translation?->label ?? $page->name,
            url: self::pageUrl($page),
            children: $children,
            lastModified: SitemapPageData::resolveLastModified($page),
            changeFrequency: self::resolveChangeFrequency($page),
            priority: self::resolvePriority($page),
            editUrl: $withEditUrl ? GetEditPageResourceUrlAction::run($page) : null,
            pageableType: $page->getMorphClass(),
            pageId: (int) $page->getKey(),
        );

        $ancestor = $page->parent;
        while ($ancestor !== null) {
            $node = new SitemapPageData(
                label: $ancestor->translation?->label ?? $ancestor->name,
                url: self::pageUrl($ancestor),
                children: collect([$node]),
                lastModified: SitemapPageData::resolveLastModified($ancestor),
                changeFrequency: self::resolveChangeFrequency($ancestor),
                priority: self::resolvePriority($ancestor),
                editUrl: $withEditUrl ? GetEditPageResourceUrlAction::run($ancestor) : null,
                pageableType: $ancestor->getMorphClass(),
                pageId: $ancestor->id,
            );

            $ancestor = $ancestor->parent;
        }

        return $node;
    }

    private static function resolveChangeFrequency(Page $page): string
    {
        return (string) ($page->meta['cache_time'] ?? 'always');
    }

    private static function resolvePriority(Page $page): float
    {
        return (float) ($page->meta['priority'] ?? 0.5);
    }

    private static function pageUrl(Page $page): string
    {
        try {
            return $page->pageUrl->full_url;
        } catch (UrlMissingSiteDomainException) {
            return $page->pageUrl->url;
        }
    }
}
