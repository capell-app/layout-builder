<?php

declare(strict_types=1);

namespace Capell\Search\Actions;

use Capell\Search\Data\SearchInsightsWindowData;
use Capell\Search\Data\SearchTermSummaryData;
use Capell\Search\Models\SearchLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildTrendingSearchesQueryAction
{
    use AsAction;

    /**
     * @return Collection<int, SearchTermSummaryData>
     */
    public function handle(SearchInsightsWindowData $window, ?int $limit = 10): Collection
    {
        $currentSummaries = BuildTopSearchesQueryAction::run($window, null);
        $previousSearchCounts = $this->previousSearchCounts($window);

        $trendingSummaries = $currentSummaries
            ->map(function (SearchTermSummaryData $summary) use ($previousSearchCounts): SearchTermSummaryData {
                $previousSearches = $previousSearchCounts[$summary->normalizedQuery] ?? 0;
                $trendPercentage = $this->calculateTrendPercentage($summary->searches, $previousSearches);

                return new SearchTermSummaryData(
                    query: $summary->query,
                    normalizedQuery: $summary->normalizedQuery,
                    searches: $summary->searches,
                    resultsCount: $summary->resultsCount,
                    trendPercentage: $trendPercentage,
                );
            })
            ->filter(fn (SearchTermSummaryData $summary): bool => $summary->trendPercentage > 0.0)
            ->sortBy([
                ['trendPercentage', 'desc'],
                ['searches', 'desc'],
            ])
            ->values();

        if ($limit === null) {
            return $trendingSummaries;
        }

        return $trendingSummaries->take($limit)->values();
    }

    /**
     * @return array<string, int>
     */
    private function previousSearchCounts(SearchInsightsWindowData $window): array
    {
        $previousStart = $this->previousWindowStart($window);

        return SearchLog::query()
            ->select([
                'normalized_query',
                DB::raw('COUNT(*) as searches'),
            ])
            ->where('searched_at', '>=', $previousStart)
            ->where('searched_at', '<', $window->start)
            ->groupBy('normalized_query')
            ->pluck('searches', 'normalized_query')
            ->mapWithKeys(fn (mixed $searches, string $normalizedQuery): array => [
                $normalizedQuery => (int) $searches,
            ])
            ->all();
    }

    private function previousWindowStart(SearchInsightsWindowData $window): CarbonImmutable
    {
        $seconds = max(1, (int) $window->start->diffInSeconds($window->end));

        return $window->start->subSeconds($seconds);
    }

    private function calculateTrendPercentage(int $currentSearches, int $previousSearches): float
    {
        if ($previousSearches === 0) {
            return $currentSearches > 0 ? 100.0 : 0.0;
        }

        return round((($currentSearches - $previousSearches) / $previousSearches) * 100, 1);
    }
}
