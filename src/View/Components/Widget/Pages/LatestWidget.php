<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget\Pages;

use Capell\Frontend\Facades\FrontendLoader;
use Capell\Frontend\Services\Loader\PageLoader;
use Illuminate\Database\Eloquent\Builder;

class LatestWidget extends AbstractPagesWidget
{
    protected function mountWidget(): void
    {
        $this->pages = PageLoader::getPages(
            site: FrontendLoader::getSite(),
            language: FrontendLoader::getLanguage(),
            page: FrontendLoader::getPage(),
            limit: $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12),
            ordering: 'latest',
            pageGroup: $this->widget->meta['page_group'] ?? null,
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            cacheKeyPrepend: 'latest-widget-' . $this->widget->id,
            modifyQuery: fn (Builder $query) => $query->whereKeyNot(FrontendLoader::getPage()->id),
        );

        $this->skipRender = $this->pages->isEmpty();
    }
}
