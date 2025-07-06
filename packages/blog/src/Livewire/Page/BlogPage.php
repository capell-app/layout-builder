<?php

declare(strict_types=1);

namespace Capell\Blog\Livewire\Page;

use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Livewire\Page\AbstractPage;
use Capell\Frontend\Services\Loader\PageLoader;

class BlogPage extends AbstractPage
{
    protected string $defaultView = 'capell::livewire.page.results';

    protected function loadPage(): void
    {
        $pageRecord = Frontend::getPage();

        $paginationKey = config('capell-admin.page_query', 'pageQuery');

        $this->results = PageLoader::getPages(
            site: Frontend::getSite(),
            language: Frontend::getLanguage(),
            limit: $pageRecord->type->meta['limit'] ?? config('capell-frontend.pagination_limit', 12),
            paginationPage: $this->getPage($paginationKey),
            pageGroup: $pageRecord->type->meta['page_group'] ?? null,
            typeKey: $pageRecord->type->meta['page_type'] ?? null,
            withImage: $pageRecord->type->meta['with_image'] ?? false,
            withPagination: $pageRecord->type->meta['pagination'] ?? true,
            withParent: $pageRecord->type->meta['with_parent'] ?? false,
            withDate: $pageRecord->type->meta['with_date'] ?? false,
            withTags: $pageRecord->type->meta['with_tags'] ?? false,
            paginationKey: $paginationKey,
        );
    }
}
