<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Support\Sitemap;

use Capell\SiteDiscovery\Contracts\Sitemapable;

class SitemapPageRegistry
{
    /** @var array<string, class-string<Sitemapable>> */
    private array $pages = [];

    /**
     * @param  class-string<Sitemapable>  $class
     */
    public function register(string $key, string $class): self
    {
        $this->pages[$key] = $class;

        return $this;
    }

    /** @return array<string, class-string<Sitemapable>> */
    public function all(): array
    {
        return $this->pages;
    }
}
