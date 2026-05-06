<?php

declare(strict_types=1);

namespace Capell\Search\Actions;

use Capell\Search\Contracts\Search;
use Capell\Search\Data\SearchRequestData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Lorisleiva\Actions\Concerns\AsAction;

final readonly class RunSearchAction
{
    use AsAction;

    public function __construct(private Search $search) {}

    public function handle(SearchRequestData $data): LengthAwarePaginator
    {
        $normalizedQuery = NormalizeSearchQueryAction::run($data->query);
        $minimumLength = (int) ResolveSearchSettingAction::run(
            'minimum_query_length',
            'capell-search.minimum_query_length',
            2,
        );

        if ($normalizedQuery === '' || mb_strlen((string) $normalizedQuery) < $minimumLength) {
            return new Paginator([], 0, $data->perPage, $data->page);
        }

        return $this->search->search($normalizedQuery, $data->perPage, $data->page);
    }
}
