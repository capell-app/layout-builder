<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Listeners\AiDiscovery;

use Capell\Core\Events\SiteCreated;
use Capell\SeoSuite\Actions\SeedDefaultAiCrawlerRulesAction;

class SeedAiCrawlerRulesOnSiteCreated
{
    public function handle(SiteCreated $event): void
    {
        SeedDefaultAiCrawlerRulesAction::run($event->site);
    }
}
