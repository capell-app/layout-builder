<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Support\Sitemap;

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\SiteDiscovery\Contracts\Sitemapable;

abstract class AbstractSitemapPages implements Sitemapable
{
    public function __construct(
        protected readonly Site $site,
        protected readonly SiteDomain $domain,
        protected readonly Language $language,
        protected readonly bool $withEditUrl = false,
    ) {}
}
