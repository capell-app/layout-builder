<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\Sitemap;

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\SeoSuite\Contracts\Sitemapable;
use Capell\SeoSuite\Data\SitemapPageData;
use Illuminate\Support\Collection;

class SitemapBuilder
{
    protected Collection $pages;

    /**
     * Map of composite key to index in $pages.
     * Key is either "{pageableType}:{pageId}" when both present, or "url:{url}" as a fallback.
     *
     * @var array<string,int>
     */
    protected array $indexByKey = [];

    public function __construct(
        protected Site $site,
        protected SiteDomain $domain,
        protected Language $language,
        protected bool $withEditUrl = false,
    ) {
        $this->pages = collect();

        /**
         * @var class-string<Sitemapable> $sitemapPageType
         */
        foreach (resolve(SitemapPageRegistry::class)->all() as $sitemapPageType) {
            $this->addPages(
                new $sitemapPageType(
                    language: $this->language,
                    site: $this->site,
                    domain: $this->domain,
                    withEditUrl: $this->withEditUrl,
                ),
            );
        }
    }

    public function addPages(Sitemapable $sitemapPageType): void
    {
        $results = $sitemapPageType->fetch();

        if ($results->isEmpty()) {
            return;
        }

        $results->each(function (SitemapPageData $item): void {
            $this->upsertTopLevel($item);
        });
    }

    public function build(): Collection
    {
        return $this->pages
            ->sortByDesc('priority')
            ->values();
    }

    private function upsertTopLevel(SitemapPageData $item): void
    {
        $key = $this->nodeKey($item);

        $index = $this->indexByKey[$key] ?? null;

        if ($index === null) {
            $this->pages->push($item);
            $newIndex = $this->pages->count() - 1;
            $this->indexByKey[$key] = $newIndex;

            return;
        }

        /** @var SitemapPageData $existing */
        $existing = $this->pages->get($index);
        $existing->children = $this->mergeChildren($existing->children ?? collect(), $item->children ?? collect());

        $existing = $this->mergeNodeAttributes($existing, $item);

        $this->pages->put($index, $existing);
    }

    private function mergeNodeAttributes(SitemapPageData $existing, SitemapPageData $incoming): SitemapPageData
    {
        foreach (['label', 'lastModified', 'changeFrequency', 'priority', 'editUrl'] as $key) {
            if (($incoming->{$key} ?? null) !== null) {
                $existing->{$key} = $incoming->{$key};
            }
        }

        return $existing;
    }

    /**
     * @param  Collection<int, SitemapPageData>  $existingChildren
     * @param  Collection<int, SitemapPageData>  $incomingChildren
     * @return Collection<int, SitemapPageData>
     */
    private function mergeChildren(Collection $existingChildren, Collection $incomingChildren): Collection
    {
        $byKey = [];
        $merged = [];

        $getKey = static function (SitemapPageData $node): string {
            if ($node->pageableType !== null && $node->pageId !== null) {
                return $node->pageableType . ':' . $node->pageId;
            }

            // fallback to URL as the deterministic identifier
            return 'url:' . $node->url;
        };

        $register = function (SitemapPageData $node, int $idx) use (&$byKey, &$merged, $getKey): void {
            $merged[$idx] = $node;

            $key = $getKey($node);
            $byKey[$key] = $idx;
        };

        foreach ($existingChildren as $idx => $child) {
            $register($child, $idx);
        }

        foreach ($incomingChildren as $child) {
            $key = $getKey($child);

            $matchIdx = $byKey[$key] ?? null;

            if ($matchIdx === null) {
                $merged[] = $child;
                $newIdx = array_key_last($merged);
                $register($child, $newIdx);

                continue;
            }

            $existing = $merged[$matchIdx];
            $existing->children = $this->mergeChildren(
                $existing->children ?? collect(),
                $child->children ?? collect(),
            );
            $existing = $this->mergeNodeAttributes($existing, $child);
            $merged[$matchIdx] = $existing;
        }

        return collect($merged)->values();
    }

    private function nodeKey(SitemapPageData $node): string
    {
        if ($node->pageableType !== null && $node->pageId !== null) {
            return $node->pageableType . ':' . $node->pageId;
        }

        return 'url:' . $node->url;
    }
}
