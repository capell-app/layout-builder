<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget\Pages;

use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Services\Loader\PageLoader;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;

class SiblingsWidget extends AbstractPagesWidget
{
    protected function mountWidget(): void
    {
        $page = Frontend::page();

        if (! empty($page->type->meta['hidden'])) {
            $this->skipRender = true;

            return;
        }

        if (! $page->parent_id) {
            $this->skipRender = true;

            return;
        }

        $this->pages = PageLoader::getPages(
            language: Frontend::language(),
            site: Frontend::site(),
            page: $page,
            type: 'siblings',
            ordering: 'alphabetical',
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            cacheKeyPrepend: 'page-not-' . $page->id,
            modifyQuery: fn (BuilderContract $query): BuilderContract => $query->whereKeyNot($page->id),
        );

        if ($this->pages->isEmpty()) {
            $this->skipRender = true;
        }
    }
}
