<?php

declare(strict_types=1);

namespace Capell\Mosaic\View\Components\Widget\Page;

use Capell\Core\Enums\PageOrderEnum;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class Latest extends AbstractPagesWidget
{
    protected static string $defaultView = 'capell-mosaic::components.widget.asset.pages';

    protected function mountWidget(): void
    {
        $morphModel = $this->widget->getMeta('page_model');

        $modelClass = null;

        if ($morphModel !== null) {
            $modelClass = Relation::getMorphedModel($morphModel);
        }

        $this->pages = PageLoader::getPages(
            language: Frontend::language(),
            site: Frontend::site(),
            page: Frontend::page(),
            limit: $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12),
            ordering: PageOrderEnum::Latest,
            pageGroup: $this->widget->meta['page_group'] ?? null,
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            cacheKeyPrepend: 'latest-widget-' . $this->widget->id,
            morphModel: $modelClass,
            modifyQuery: fn (Builder $query) => $query->whereKeyNot(Frontend::page()->id),
        );

        if ($this->pages->isEmpty() && config('capell-mosaic.widget.skip_render_empty', true)) {
            $this->skipRender = true;
        }
    }
}
