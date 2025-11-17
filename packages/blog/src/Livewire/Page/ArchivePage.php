<?php

declare(strict_types=1);

namespace Capell\Blog\Livewire\Page;

use Capell\Blog\Enums\ResourceEnum;
use Capell\Frontend\CapellFrontendManager;
use Capell\Frontend\Facades\FrontendLoader;
use Capell\Frontend\Livewire\Page\AbstractPage;
use Capell\Frontend\Services\Loader\PageLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class ArchivePage extends AbstractPage
{
    public ?int $month = null;

    public ?int $year = null;

    protected static string $defaultView = 'capell::livewire.page.results';

    public function getPaginationPage($pageName = 'page')
    {
        return request()->get($pageName) ?? parent::getPage($pageName);
    }

    /**
     * @return array<int, int>
     */
    protected function getArchiveDateFromUrl(): array
    {
        $current = FrontendLoader::getPageSlug();

        $month = null;
        $year = null;

        if ($current === '' || $current === '0') {
            CapellFrontendManager::throwErrorPage();
        }

        $parts = explode('/', $current);
        $dates = explode('-', $parts[0]);

        $current = isset($parts[1]) ? (int) $parts[1] : 1;

        if (isset($dates[0]) && mb_strlen($dates[0]) === 4) {
            $year = (int) $dates[0];
        }

        if (isset($dates[1]) && $dates[1] >= 0 && $dates[1] <= 12) {
            $month = (int) $dates[1];
        }

        if (! is_numeric($current) && ($year === 0 || $year === null)) {
            CapellFrontendManager::throwErrorPage();
        }

        return [$year, $month];
    }

    protected function getViewData(): array
    {
        $date = Date::create()->day(1)->month($this->month)->year($this->year);

        return [
            'archive_date' => $date,
            'archive_month' => $date->format('F'),
            'archive_year' => $this->year,
        ];
    }

    protected function loadPage(): void
    {
        if (($this->year === null || $this->year === 0) && ($this->month === null || $this->month === 0)) {
            [$this->year, $this->month] = $this->getArchiveDateFromUrl();
        }

        abort_if(($this->year === null || $this->year === 0) && ($this->month === null || $this->month === 0), 404);

        $page = FrontendLoader::getPage();

        $paginationKey = config('capell-admin.page_query', 'pageQuery');

        $this->results = PageLoader::getPages(
            site: FrontendLoader::getSite(),
            language: FrontendLoader::getLanguage(),
            limit: $page->type->meta['limit'] ?? config('capell-frontend.pagination_limit', 12),
            paginationPage: $this->getPage($paginationKey),
            typeKey: $page->type->meta['page_group'] ?? strtolower(ResourceEnum::Article->name),
            withImage: $page->type->meta['with_image'] ?? false,
            withPagination: $page->type->meta['pagination'] ?? true,
            withParent: $page->type->meta['with_parent'] ?? false,
            withDate: $page->type->meta['with_date'] ?? false,
            paginationKey: $paginationKey,
            cacheKeyPrepend: sprintf('year-%s-month-%s', $this->year, $this->month),
            modifyQuery: function (Builder $query) {
                if (DB::getDriverName() === 'sqlite') {
                    return $query->when(
                        $this->year,
                        fn (Builder $query) => $query->whereRaw("strftime('%Y', COALESCE(`publish_from`, `created_at`)) = " . (int) $this->year),
                    )
                        ->when(
                            $this->month,
                            fn (Builder $query) => $query->whereRaw("strftime('%m', COALESCE(`publish_from`, `created_at`)) = " . (int) $this->month),
                        );
                }

                return $query->when(
                    $this->year,
                    fn (Builder $query) => $query->whereRaw('YEAR(COALESCE(`publish_from`, `created_at`)) = ' . (int) $this->year),
                )
                    ->when(
                        $this->month,
                        fn (Builder $query) => $query->whereRaw('MONTH(COALESCE(`publish_from`, `created_at`)) = ' . (int) $this->month),
                    );
            },
        );

        $this->pageParams = $this->getViewData();

        FrontendLoader::setPageParams($this->pageParams);
    }
}
