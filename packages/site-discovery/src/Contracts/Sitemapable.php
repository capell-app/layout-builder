<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Contracts;

use Illuminate\Support\Collection;

interface Sitemapable
{
    public function fetch(): Collection;
}
