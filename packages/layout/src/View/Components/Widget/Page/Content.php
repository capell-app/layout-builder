<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget\Page;

use Capell\Core\Contracts\Pageable;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Layout\View\Components\Widget\AbstractWidget;

class Content extends AbstractWidget
{
    public ?Pageable $nextPage = null;

    public ?Pageable $previousPage = null;

    protected static string $defaultView = 'capell-layout::components.widget.page.content';

    protected function mountWidget(): void
    {
        $page = Frontend::page();
        $language = Frontend::language();
        $site = Frontend::site();

        if ((bool) $page->getMeta('with_next_prev')) {
            $this->previousPage = PageLoader::getPreviousPage($page, $site, $language);
            $this->nextPage = PageLoader::getNextPage($page, $site, $language);
        }
    }
}
