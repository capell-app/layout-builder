<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Listeners\AiDiscovery;

use Capell\Core\Events\PageSaved;
use Capell\Core\Models\Page;
use Capell\SeoSuite\Actions\ClearAiDiscoveryCacheAction;

class ClearAiDiscoveryCacheOnPageSaved
{
    public function handle(PageSaved $event): void
    {
        $page = $event->page;

        if (! $page instanceof Page || $page->site === null) {
            return;
        }

        ClearAiDiscoveryCacheAction::run($page->site, page: $page);
    }
}
