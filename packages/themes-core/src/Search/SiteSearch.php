<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Search;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for site search backends. A theme can ship its own Scout- or
 * Meilisearch-backed implementation; the default ships as DatabaseSiteSearch.
 */
interface SiteSearch
{
    /**
     * @return LengthAwarePaginator<int, SearchResult>
     */
    public function search(string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator;

    public function highlight(string $text, string $query): string;
}
