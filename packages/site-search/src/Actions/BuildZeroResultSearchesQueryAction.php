<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Actions;

use Capell\SiteSearch\Data\SearchAnalyticsWindowData;
use Capell\SiteSearch\Data\SearchTermSummaryData;
use Capell\SiteSearch\Models\SiteSearchLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildZeroResultSearchesQueryAction
{
    use AsAction;

    /**
     * @return Collection<int, SearchTermSummaryData>
     */
    public function handle(SearchAnalyticsWindowData $window, ?int $limit = 10): Collection
    {
        $query = SiteSearchLog::query()
            ->select([
                'normalized_query',
                DB::raw('MIN(query) as query'),
                DB::raw('COUNT(*) as searches'),
                DB::raw('SUM(results_count) as results_count'),
            ])
            ->whereBetween('searched_at', [$window->start, $window->end])
            ->where('results_count', 0)
            ->groupBy('normalized_query')
            ->orderByDesc('searches');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get()
            ->map(fn (SiteSearchLog $log): SearchTermSummaryData => new SearchTermSummaryData(
                query: $log->query,
                normalizedQuery: $log->normalized_query,
                searches: $log->searches,
                resultsCount: $log->results_count,
            ))
            ->values();
    }
}
