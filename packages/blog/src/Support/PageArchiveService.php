<?php

declare(strict_types=1);

namespace Capell\Blog\Support;

use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Models\Article;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use stdClass;

class PageArchiveService
{
    /**
     * Returns archive counts grouped by year and month, optionally paginated.
     *
     * @param  bool  $paginate  Whether to paginate the results
     * @param  int|null  $perPage  Number of items per page if paginating
     * @return LengthAwarePaginator<ArchiveMonthData>
     */
    public function getArchivedCountsByMonth(
        Site $site,
        Language $language,
        string $group,
        bool $paginate = false,
        ?int $perPage = null,
        ?string $paginationKey = null,
    ) {
        $query = Article::query()
            ->selectRaw('COUNT(*) as `total`')
            ->when(
                DB::getDriverName() === 'sqlite',
                fn (Builder $query): Builder => $query->addSelect([
                    DB::raw("strftime('%Y', COALESCE(`visible_from`, `created_at`)) as year"),
                    DB::raw("strftime('%m', COALESCE(`visible_from`, `created_at`)) as month"),
                ]),
                fn (Builder $query): Builder => $query->addSelect([
                    DB::raw('YEAR(COALESCE(`visible_from`, `created_at`)) as year'),
                    DB::raw('MONTH(COALESCE(`visible_from`, `created_at`)) as month'),
                ]),
            )
            ->whereHas(
                'type',
                function (Builder $query) use ($group): void {
                    $query->where('group', $group)->enabled()->visible();
                },
            )
            ->whereHas(
                'translation',
                function (Builder $query) use ($language): void {
                    $query->where('language_id', $language->id);
                },
            )
            ->where('site_id', $site->id)
            ->published()
            ->publishedDate()
            ->when(
                DB::getDriverName() === 'sqlite',
                fn (Builder $query): Builder => $query->groupByRaw("strftime('%Y', COALESCE(`visible_from`, `created_at`)), strftime('%m', COALESCE(`visible_from`, `created_at`))"),
                fn (Builder $query): Builder => $query->groupByRaw('YEAR(COALESCE(`visible_from`, `created_at`)), MONTH(COALESCE(`visible_from`, `created_at`))'),
            )
            ->orderByRaw('COALESCE(`visible_from`, `created_at`) DESC');

        if ($paginate) {
            $paginator = $query->getQuery()->paginate($perPage ?? 15, pageName: $paginationKey);

            $paginator->getCollection()->transform(fn (stdClass $row): ArchiveMonthData => new ArchiveMonthData(
                (int) $row->year,
                (int) $row->month,
                (int) $row->total,
            ));

            return $paginator;
        }

        return $query->getQuery()
            ->get()
            ->map(
                fn (stdClass $row): ArchiveMonthData => new ArchiveMonthData(
                    (int) $row->year,
                    (int) $row->month,
                    (int) $row->total,
                ),
            );
    }
}
