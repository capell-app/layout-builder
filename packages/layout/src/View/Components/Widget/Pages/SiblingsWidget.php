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
        $pageRecord = Frontend::getPage();

        if (! empty($pageRecord->type->meta['hidden'])) {
            $this->skipRender = true;

            return;
        }

        if (! $pageRecord->parent_id) {
            $this->skipRender = true;

            return;
        }

        $this->pages = PageLoader::getPages(
            site: Frontend::getSite(),
            language: Frontend::getLanguage(),
            page: $pageRecord,
            type: 'siblings',
            ordering: 'alphabetical',
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            withTags: $this->widget->meta['with_tags'] ?? false,
            modifyQuery: fn (BuilderContract $query) => $query->whereKeyNot($pageRecord->id)
        );

        if ($this->pages->isEmpty()) {
            $this->skipRender = true;
        }
    }
}
