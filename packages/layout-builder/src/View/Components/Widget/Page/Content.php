<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\View\Components\Widget\Page;

use Capell\Core\Contracts\Pageable;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\LayoutBuilder\View\Components\Widget\AbstractWidget;
use Closure;
use Illuminate\Contracts\View\View;

class Content extends AbstractWidget
{
    public ?Pageable $nextPage = null;

    public ?Pageable $previousPage = null;

    protected static string $defaultView = 'capell-layout-builder::components.widget.page.content';

    public function render(array $data = []): View|string|Closure
    {
        return parent::render([
            ...$data,
            'previousPage' => $this->previousPage,
            'nextPage' => $this->nextPage,
        ]);
    }

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
