<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Search;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

/**
 * Default DB-backed search. Runs a LIKE query against the configured table and
 * columns. Suitable as a fallback until a theme wires in Scout or Meilisearch.
 */
class DatabaseSiteSearch implements SiteSearch
{
    /**
     * @param  list<string>  $columns  Columns to search against.
     */
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly string $table = 'pages',
        private readonly array $columns = ['title', 'excerpt', 'body'],
        private readonly string $urlColumn = 'slug',
        private readonly string $typeColumn = 'type',
    ) {}

    public function search(string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $query = trim($query);
        if ($query === '') {
            return new Paginator([], 0, $perPage, $page);
        }

        $builder = $this->db->table($this->table);
        $builder->where(function ($q) use ($query): void {
            foreach ($this->columns as $column) {
                $q->orWhere($column, 'like', '%'.$query.'%');
            }
        });

        $total = (clone $builder)->count();

        $rows = $builder
            ->forPage($page, $perPage)
            ->get();

        $results = (new Collection($rows))->map(function ($row) use ($query): SearchResult {
            $title = (string) ($row->title ?? '');
            $excerptRaw = (string) ($row->excerpt ?? $row->body ?? '');

            return new SearchResult(
                title: $title,
                url: '/'.ltrim((string) ($row->{$this->urlColumn} ?? ''), '/'),
                excerpt: $this->truncate($excerptRaw, 200),
                type: (string) ($row->{$this->typeColumn} ?? 'page'),
                score: $this->score($title.' '.$excerptRaw, $query),
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
        $pattern = '/('.preg_quote($query, '/').')/i';

        return (string) preg_replace($pattern, '<mark>$1</mark>', $escaped);
    }

    private function truncate(string $text, int $length): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length)).'…';
    }

    private function score(string $haystack, string $needle): float
    {
        $count = substr_count(mb_strtolower($haystack), mb_strtolower($needle));

        return (float) $count;
    }
}
