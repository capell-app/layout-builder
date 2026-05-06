<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Listeners\Sitemap;

use Capell\Core\Events\PageDeleted;
use Capell\SeoSuite\Support\Sitemap\XmlSitemapGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegenerateSitemapsOnPageDeleted implements ShouldQueue
{
    public function __construct(private readonly XmlSitemapGenerator $generator) {}

    public function handle(PageDeleted $event): void
    {
        $site = $event->page->site;

        if ($site === null) {
            return;
        }

        $this->generator->processIncremental($site);
    }
}
