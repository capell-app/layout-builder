<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Contracts;

use Capell\SiteSearch\Data\SearchResultData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for site search backends. A site can use the default database
 * driver or bind a Scout-backed implementation.
 */
interface SiteSearch
{
    /**
     * @return LengthAwarePaginator<int, SearchResultData>
     */
    public function search(string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator;

    public function highlight(string $text, string $query): string;
}
