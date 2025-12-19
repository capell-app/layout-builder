<?php

declare(strict_types=1);

namespace Capell\Blog\Livewire\Page;

use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Livewire\Page\AbstractPage;
use Capell\Frontend\Services\Loader\PageLoader;
use Illuminate\Database\Eloquent\Builder;

class BlogPage extends AbstractPage
{
    protected static string $defaultView = 'capell::livewire.page.results';

    protected function loadPage(): void
    {
        $page = Frontend::page();

        $paginationKey = config('capell-admin.page_query', 'pageQuery');

        $this->results = PageLoader::getPages(
            language: Frontend::language(),
            site: Frontend::site(),
            limit: $page->type->meta['limit'] ?? config('capell-frontend.pagination_limit', 12),
            paginationPage: $this->getPage($paginationKey),
            pageGroup: $page->type->meta['page_group'] ?? null,
            typeKey: $page->type->meta['page_type'] ?? null,
            withImage: $page->type->meta['with_image'] ?? false,
            withPagination: $page->type->meta['pagination'] ?? true,
            withParent: $page->type->meta['with_parent'] ?? false,
            withDate: $page->type->meta['with_date'] ?? false,
            paginationKey: $paginationKey,
            modifyQuery: fn (Builder $query) => $query->with(['tags']),
        );
    }
}
