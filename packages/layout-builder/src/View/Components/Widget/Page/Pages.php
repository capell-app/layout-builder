<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\View\Components\Widget\Page;

use Capell\Core\Enums\PageOrderEnum;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class Pages extends AbstractPagesWidget
{
    protected static string $defaultView = 'capell-layout-builder::components.widget.asset.pages';

    protected function mountWidget(): void
    {
        $page = Frontend::page();
        $selection = $this->widget->assets()->pluck('asset_id')->all();

        $morphModel = $this->widget->getMeta('page_model');

        if ($morphModel !== null) {
            $morphModel = Relation::getMorphedModel($morphModel);
        }

        $this->pages = PageLoader::getPages(
            language: Frontend::language(),
            site: Frontend::site(),
            page: $page,
            limit: $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12),
            ordering: ($this->widget->meta['order'] ?? '') === '' ? null : PageOrderEnum::from($this->widget->meta['order']),
            pageGroup: $this->widget->meta['page_group'] ?? null,
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            cacheKeyPrepend: 'pages-widget-' . $this->widget->id,
            morphModel: $morphModel,
            useCache: false,
            modifyQuery: fn (Builder $query): Builder => $query->whereIn('id', $selection),
        );

        if ($this->pages->isEmpty() && config('capell-layout-builder.widget.skip_render_empty', true)) {
            $this->skipRender = true;
        }
    }
}
