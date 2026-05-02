<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Drivers;

use Capell\SiteSearch\Contracts\SiteSearch;
use Capell\SiteSearch\Data\SearchResultData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

/**
 * Default DB-backed search. Runs a LIKE query against the configured table and
 * columns. Suitable as a fallback until a site wires in Scout or Meilisearch.
 */
class DatabaseSiteSearch implements SiteSearch
{
    private const MINIMUM_QUERY_LENGTH = 2;

    private const MINIMUM_PER_PAGE = 1;

    private const MAXIMUM_PER_PAGE = 100;

    /**
     * @param  list<string>  $columns  Columns to search against.
     */
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly string $table = 'pages',
        private readonly array $columns = ['title', 'excerpt', 'body'],
        private readonly string $urlColumn = 'slug',
        private readonly string $typeColumn = 'type',
        private readonly string $titleColumn = 'title',
        private readonly string $excerptColumn = 'excerpt',
        private readonly string $bodyColumn = 'body',
    ) {}

    public function search(string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $query = trim($query);
        $perPage = max(self::MINIMUM_PER_PAGE, min(self::MAXIMUM_PER_PAGE, $perPage));
        $page = max(1, $page);

        if ($query === '' || mb_strlen($query) < self::MINIMUM_QUERY_LENGTH) {
            return new Paginator([], 0, $perPage, $page);
        }

        $likeQuery = '%' . $this->escapeLike($query) . '%';
        $builder = $this->db->table($this->table);
        $builder->where(function (Builder $queryBuilder) use ($likeQuery): void {
            foreach ($this->columns as $column) {
                $queryBuilder->orWhereRaw(
                    $queryBuilder->getGrammar()->wrap($column) . " LIKE ? ESCAPE '!'",
                    [$likeQuery],
                );
            }
        });

        $total = (clone $builder)->count();

        $rows = $builder
            ->forPage($page, $perPage)
            ->get();

        $results = (new Collection($rows))->map(function (object $row) use ($query): SearchResultData {
            $title = (string) ($row->{$this->titleColumn} ?? '');
            $excerptRaw = (string) ($row->{$this->excerptColumn} ?? $row->{$this->bodyColumn} ?? '');

            return new SearchResultData(
                title: $title,
                url: '/' . ltrim((string) ($row->{$this->urlColumn} ?? ''), '/'),
                excerpt: $this->truncate($excerptRaw, 200),
                type: (string) ($row->{$this->typeColumn} ?? 'page'),
                score: $this->score($title . ' ' . $excerptRaw, $query),
            );
        });

        return new Paginator($results, $total, $perPage, $page);
    }

    public function highlight(string $text, string $query): string
    {
        $query = trim($query);
        if ($query === '') {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }

        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $pattern = '/(' . preg_quote($query, '/') . ')/i';

        return (string) preg_replace($pattern, '<mark>$1</mark>', $escaped);
    }

    private function truncate(string $text, int $length): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length)) . '...';
    }

    private function escapeLike(string $query): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query);
    }

    private function score(string $haystack, string $needle): float
    {
        $count = substr_count(mb_strtolower($haystack), mb_strtolower($needle));

        return (float) $count;
    }
}
