<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Actions;

use Capell\SiteSearch\Data\SearchRequestData;
use Capell\SiteSearch\Models\SiteSearchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordSiteSearchAction
{
    use AsAction;

    public function handle(SearchRequestData $data, int $resultsCount, Request $request): ?SiteSearchLog
    {
        if (! (bool) ResolveSiteSearchSettingAction::run(
            'record_search_logs',
            'capell-site-search.record_search_logs',
            true,
        )) {
            return null;
        }

        $normalizedQuery = NormalizeSearchQueryAction::run($data->query);
        $minimumLength = (int) ResolveSiteSearchSettingAction::run(
            'minimum_query_length',
            'capell-site-search.minimum_query_length',
            2,
        );

        if ($normalizedQuery === '' || mb_strlen((string) $normalizedQuery) < $minimumLength) {
            return null;
        }

        if (! SchemaFacade::hasTable((new SiteSearchLog)->getTable())) {
            return null;
        }

        return SiteSearchLog::query()->create([
            'site_id' => $data->siteId,
            'language_id' => $data->languageId,
            'query' => $data->query,
            'normalized_query' => $normalizedQuery,
            'results_count' => $resultsCount,
            'ip_hash' => $this->hashVisitorValue($request->ip()),
            'user_agent_hash' => $this->hashVisitorValue($request->userAgent()),
            'searched_at' => now(),
        ]);
    }

    private function hashVisitorValue(?string $value): ?string
    {
        if (! (bool) ResolveSiteSearchSettingAction::run(
            'hash_visitor_data',
            'capell-site-search.hash_visitor_data',
            true,
        )) {
            return null;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return hash('sha256', $value . '|' . config('app.key'));
    }
}
