<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Listeners\Sitemap;

use Capell\Core\Events\SiteCreated;
use Capell\SiteDiscovery\Support\Sitemap\XmlSitemapGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegenerateSitemapsOnSiteCreated implements ShouldQueue
{
    public function __construct(private readonly XmlSitemapGenerator $generator) {}

    public function handle(SiteCreated $event): void
    {
        $this->generator->processIncremental($event->site);
    }
}
