<?php

declare(strict_types=1);

namespace Capell\Search\Actions;

use Capell\Search\Data\SearchInsightsWindowData;
use Capell\Search\Data\SearchTermSummaryData;
use Capell\Search\Models\SearchLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildTopSearchesQueryAction
{
    use AsAction;

    /**
     * @return Collection<int, SearchTermSummaryData>
     */
    public function handle(SearchInsightsWindowData $window, ?int $limit = 10): Collection
    {
        $query = SearchLog::query()
            ->select([
                'normalized_query',
                DB::raw('MIN(query) as query'),
                DB::raw('COUNT(*) as searches'),
                DB::raw('SUM(results_count) as results_count'),
            ])
            ->whereBetween('searched_at', [$window->start, $window->end])
            ->groupBy('normalized_query')
            ->orderByDesc('searches');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get()
            ->map(fn (SearchLog $log): SearchTermSummaryData => new SearchTermSummaryData(
                query: $log->query,
                normalizedQuery: $log->normalized_query,
                searches: $log->searches,
                resultsCount: $log->results_count,
            ))
            ->values();
    }
}
