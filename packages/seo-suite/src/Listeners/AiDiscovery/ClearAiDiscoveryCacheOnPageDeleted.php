<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Listeners\AiDiscovery;

use Capell\Core\Events\PageDeleted;
use Capell\Core\Models\Page;
use Capell\SeoSuite\Actions\ClearAiDiscoveryCacheAction;

class ClearAiDiscoveryCacheOnPageDeleted
{
    public function handle(PageDeleted $event): void
    {
        $page = $event->page;

        if (! $page instanceof Page || $page->site === null) {
            return;
        }

        ClearAiDiscoveryCacheAction::run($page->site, page: $page);
    }
}
