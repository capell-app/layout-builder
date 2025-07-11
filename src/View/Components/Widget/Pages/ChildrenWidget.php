<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget\Pages;

use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Services\Loader\PageLoader;

class ChildrenWidget extends AbstractPagesWidget
{
    protected function mountWidget(): void
    {
        if (! empty(Frontend::getPage()->type->meta['hidden'])) {
            $this->skipRender = true;

            return;
        }

        $this->pages = PageLoader::getPages(
            site: Frontend::getSite(),
            language: Frontend::getLanguage(),
            page: Frontend::getPage(),
            type: 'children',
            ordering: 'alphabetical',
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            withTags: $this->widget->meta['with_tags'] ?? false,
        );

        if ($this->pages->isEmpty()) {
            $this->skipRender = true;
        }
    }
}
