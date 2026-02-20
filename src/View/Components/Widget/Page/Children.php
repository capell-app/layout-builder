<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget\Page;

use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;

class Children extends AbstractPagesWidget
{
    protected static string $defaultView = 'capell-layout::components.widget.asset.pages';

    protected function mountWidget(): void
    {
        if (! empty(Frontend::page()->type->meta['hidden'])) {
            $this->skipRender = true;

            return;
        }

        $this->pages = PageLoader::getPages(
            language: Frontend::language(),
            site: Frontend::site(),
            page: Frontend::page(),
            type: 'children',
            ordering: 'alphabetical',
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
        );

        if ($this->pages->isEmpty()) {
            $this->skipRender = true;
        }
    }
}
