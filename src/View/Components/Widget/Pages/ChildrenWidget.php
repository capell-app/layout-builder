<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget\Pages;

use Capell\Frontend\Facades\FrontendLoader;
use Capell\Frontend\Services\Loader\PageLoader;

class ChildrenWidget extends AbstractPagesWidget
{
    protected function mountWidget(): void
    {
        if (! empty(FrontendLoader::getPage()->type->meta['hidden'])) {
            $this->skipRender = true;

            return;
        }

        $this->pages = PageLoader::getPages(
            site: FrontendLoader::getSite(),
            language: FrontendLoader::getLanguage(),
            page: FrontendLoader::getPage(),
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
