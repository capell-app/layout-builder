<?php

declare(strict_types=1);

namespace Capell\Blog\Livewire\Page;

use Capell\Core\Enums\PageOrderEnum;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Livewire\Page\AbstractPage;
use Capell\Frontend\Support\Loader\PageLoader;
use Illuminate\Database\Eloquent\Builder;

class Blog extends AbstractPage
{
    protected static string $defaultView = 'capell::livewire.page.results';

    protected function setup(): void
    {
        $page = Frontend::page();

        $paginationPage = config('capell-admin.page_query', 'pageQuery');

        $this->results = PageLoader::getPages(
            language: Frontend::language(),
            site: Frontend::site(),
            limit: $page->meta['limit'] ?? $page->type->meta['limit'] ?? config('capell-frontend.pagination_limit', 12),
            paginationPage: (int) $this->getPage($paginationPage),
            ordering: $page->type->meta['ordering'] ?? PageOrderEnum::Latest,
            pageGroup: $page->type->meta['page_group'] ?? null,
            typeKey: $page->type->meta['page_type'] ?? null,
            withImage: $page->type->meta['with_image'] ?? false,
            withPagination: $page->type->meta['pagination'] ?? true,
            withParent: $page->type->meta['with_parent'] ?? false,
            withDate: $page->type->meta['with_date'] ?? false,
            paginationKey: 'articles',
            morphModel: 'article',
            modifyQuery: fn (Builder $query) => $query->with(['tags']),
        );
    }
}
