<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\View\Components\Widget\Page;

use Capell\Core\Enums\PageOrderEnum;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;

class Siblings extends AbstractPagesWidget
{
    protected static string $defaultView = 'capell-layout-builder::components.widget.asset.pages';

    protected function mountWidget(): void
    {
        $page = Frontend::page();

        if (isset($page->type->meta['hidden']) && $page->type->meta['hidden'] === true) {
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
            ordering: PageOrderEnum::Alphabetical,
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            cacheKeyPrepend: 'page-not-' . $page->id,
            useCache: false,
            modifyQuery: fn (BuilderContract $query): BuilderContract => $query->whereKeyNot($page->id),
        );

        if ($this->pages->isEmpty()) {
            $this->skipRender = true;
        }
    }
}
