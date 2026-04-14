<?php

declare(strict_types=1);

namespace Capell\Blog\Livewire\Page;

use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Livewire\Page\AbstractPage;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Frontend\Support\State\FrontendState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class Archive extends AbstractPage
{
    public ?int $month = null;

    public ?int $year = null;

    protected static string $defaultView = 'capell::livewire.page.results';

    protected function setup(): void
    {
        if (($this->year === null || $this->year === 0) && ($this->month === null || $this->month === 0)) {
            [$this->year, $this->month] = $this->getArchiveDateFromUrl();
        }

        abort_if(($this->year === null || $this->year === 0) && ($this->month === null || $this->month === 0), 404);

        $page = Frontend::page();

        $paginationPage = config('capell-admin.page_query', 'pageQuery');

        $this->results = PageLoader::getPages(
            language: Frontend::language(),
            site: Frontend::site(),
            limit: $page->meta['limit'] ?? $page->type->meta['limit'] ?? config('capell-frontend.pagination_limit', 12),
            paginationPage: (int) $this->getPage($paginationPage),
            typeKey: $page->type->meta['page_group'] ?? strtolower(ResourceEnum::Article->name),
            withImage: $page->type->meta['with_image'] ?? false,
            withPagination: $page->type->meta['pagination'] ?? true,
            withDate: $page->type->meta['with_date'] ?? false,
            paginationKey: 'article-archives',
            cacheKeyPrepend: sprintf('year-%s-month-%s', $this->year, $this->month),
            morphModel: CapellCore::getModel(ModelEnum::Article),
            modifyQuery: function (Builder $query) {
                if (DB::getDriverName() === 'sqlite') {
                    return $query
                        ->when(
                            $this->year,
                            fn (Builder $query): Builder => $query->whereRaw(
                                "strftime('%Y', COALESCE(`visible_from`, `created_at`)) = ?",
                                [(string) $this->year],
                            ),
                        )
                        ->when(
                            $this->month,
                            function (Builder $query): Builder {
                                $month = str_pad((string) $this->month, 2, '0', STR_PAD_LEFT);

                                return $query->whereRaw(
                                    "strftime('%m', COALESCE(`visible_from`, `created_at`)) = ?",
                                    [$month],
                                );
                            },
                        );
                }

                return $query
                    ->when(
                        $this->year,
                        fn (Builder $query): Builder => $query->whereRaw(
                            'YEAR(COALESCE(`visible_from`, `created_at`)) = ?',
                            [$this->year],
                        ),
                    )
                    ->when(
                        $this->month,
                        fn (Builder $query): Builder => $query->whereRaw(
                            'MONTH(COALESCE(`visible_from`, `created_at`)) = ?',
                            [$this->month],
                        ),
                    );
            },
        );

        abort_if($this->results->isEmpty(), 404);

        $this->params = $this->getViewData();

        resolve(FrontendState::class)->withParams($this->params);
    }

    /**
     * @return array<int, int>
     */
    protected function getArchiveDateFromUrl(): array
    {
        $params = Frontend::params();
        $date = is_array($params) ? ($params['date'] ?? '') : '';

        $month = null;
        $year = null;
        abort_if($date === '' || $date === '0', 404);

        $parts = explode('/', (string) $date);
        $dates = explode('-', $parts[0]);

        $date = isset($parts[1]) ? (int) $parts[1] : 1;

        if (isset($dates[0]) && mb_strlen($dates[0]) === 4) {
            $year = (int) $dates[0];
        }

        if (isset($dates[1]) && $dates[1] >= 0 && $dates[1] <= 12) {
            $month = (int) $dates[1];
        }

        abort_if(! is_numeric($date) && ($year === 0 || $year === null), 404);

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
}
