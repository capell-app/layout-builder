<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Actions;

use Capell\SiteSearch\Contracts\SiteSearch;
use Capell\SiteSearch\Data\SearchRequestData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Lorisleiva\Actions\Concerns\AsAction;

final readonly class RunSiteSearchAction
{
    use AsAction;

    public function __construct(private SiteSearch $search) {}

    public function handle(SearchRequestData $data): LengthAwarePaginator
    {
        $normalizedQuery = NormalizeSearchQueryAction::run($data->query);
        $minimumLength = (int) ResolveSiteSearchSettingAction::run(
            'minimum_query_length',
            'capell-site-search.minimum_query_length',
            2,
        );

        if ($normalizedQuery === '' || mb_strlen((string) $normalizedQuery) < $minimumLength) {
            return new Paginator([], 0, $data->perPage, $data->page);
        }

        return $this->search->search($normalizedQuery, $data->perPage, $data->page);
    }
}
