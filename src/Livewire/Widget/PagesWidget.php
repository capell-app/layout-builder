<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Widget;

use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Services\Loader\PageLoader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

class PagesWidget extends AbstractWidget
{
    use WithPagination;

    protected static string $defaultView = 'capell-layout::components.widget.page.pages';

    protected Collection|LengthAwarePaginator $pages;

    public function render(array $data = [])
    {
        $data['pages'] = $this->pages;

        return parent::render($data);
    }

    protected function mountWidget(): void
    {
        $page = Frontend::page();

        $limit = $this->widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12);

        $paginationKey = $this->containerKey . ucfirst((string) $this->widget->key) . $this->occurrence;
        $paginationPage = $this->getPage($paginationKey);

        $selection = $this->widget->assets->pluck('asset_id')->toArray();

        $this->pages = PageLoader::getPages(
            site: Frontend::site(),
            language: Frontend::language(),
            page: $page,
            limit: $limit,
            paginationPage: $paginationPage,
            ordering: $this->widget->meta['order'] ?? 'alphabetic',
            pageGroup: $this->widget->meta['page_group'] ?? null,
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withPagination: $this->widget->meta['pagination'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            paginationKey: $paginationKey,
            cacheKeyPrepend: sprintf('page-%d-widget-%d-container-%s-%d', $page->id, $this->widget->id, $this->containerKey, $this->occurrence),
            modifyQuery: fn (Builder $query) => $query->when(
                $selection,
                fn (Builder $query) => $query->whereIn('id', $selection),
            ),
        );

        if ($this->pages->isEmpty() && config('capell-layout.widget.hide_empty')) {
            $this->skipRender = true;
        }
    }
}
