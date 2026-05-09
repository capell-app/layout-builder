<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Listeners\Sitemap;

use Capell\Core\Events\PageSaved;
use Capell\SiteDiscovery\Support\Sitemap\XmlSitemapGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegenerateSitemapsOnPageSaved implements ShouldQueue
{
    public function __construct(private readonly XmlSitemapGenerator $generator) {}

    public function handle(PageSaved $event): void
    {
        $site = $event->page->site;

        if ($site === null) {
            return;
        }

        $this->generator->processIncremental($site);
    }
}
