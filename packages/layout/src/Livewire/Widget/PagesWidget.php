<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Widget;

use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Services\Loader\PageLoader;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

class PagesWidget extends AbstractWidget
{
    use WithPagination;

    protected string $defaultView = 'capell::components.widget.page.pages';

    protected Collection|LengthAwarePaginator $pages;

    public function render(array $data = [])
    {
        $data['pages'] = $this->pages;

        return parent::render($data);
    }

    protected function mountWidget(): void
    {
        $page = Frontend::getPage();

        $limit = $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12);

        $paginationKey = $this->containerKey.ucfirst((string) $this->widget->key).$this->occurrence;
        $paginationPage = $this->getPage($paginationKey);

        $hasPageAssets = $this->widget
            ->pageAssets($page, $this->containerKey, $this->occurrence)
            ->exists();

        if ($hasPageAssets) {
            $selection = $this->widget
                ->pageAssets($page, $this->containerKey, $this->occurrence)
                ->pluck('asset_id')
                ->toArray();
        } else {
            $selection = $this->widget->widgetAssets->pluck('asset_id')->toArray();
        }

        $this->pages = PageLoader::getPages(
            site: Frontend::getSite(),
            language: Frontend::getLanguage(),
            page: $page,
            limit: $limit,
            paginationPage: $paginationPage,
            ordering: $this->widget->meta['order'] ?? 'alphabetic',
            pageGroup: $this->widget->meta['page_group'] ?? null,
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withPagination: $this->widget->meta['pagination'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withPublished: $this->widget->meta['with_published'] ?? false,
            withTags: $this->widget->meta['with_tags'] ?? false,
            paginationKey: $paginationKey,
            cacheKeyPrepend: sprintf('page-%d-widget-%d-container-%s-%d', $page->id, $this->widget->id, $this->containerKey, $this->occurrence),
            modifyQuery: fn (Builder $query) => $query->when(
                $selection,
                fn (Builder $query) => $query->whereIn('uuid', $selection)
            )
                ->with(
                    'widgetAssets',
                    fn (BuilderContract $query) => $query->where('page_id', $page->id)
                        ->where('widget_id', $this->widget->id)
                        ->where('container', $this->containerKey)
                        ->where('occurrence', $this->occurrence)
                )
        );

        if ($this->pages->isEmpty()) {
            $this->skipRender = true;
        }
    }
}
