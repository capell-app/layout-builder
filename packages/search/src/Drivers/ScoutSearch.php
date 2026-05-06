<?php

declare(strict_types=1);

namespace Capell\Search\Drivers;

use Capell\Search\Contracts\Search;
use Capell\Search\Data\SearchResultData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

/**
 * Search driver backed by Laravel Scout.
 *
 * Bind this in your ServiceProvider when Meilisearch/Algolia/Typesense is
 * configured:
 *
 *   $this->app->bind(Search::class, fn (): Search =>
 *       new ScoutSearch(\App\Models\Page::class, urlColumn: 'slug')
 *   );
 *
 * The model class must use the Searchable trait. The driver maps model
 * instances to SearchResultData objects using toArray().
 */
class ScoutSearch implements Search
{
    /**
     * @param  class-string  $modelClass  Eloquent model that uses the Searchable trait
     */
    public function __construct(
        private readonly string $modelClass,
        private readonly string $urlColumn = 'slug',
        private readonly string $typeColumn = 'type',
        private readonly int $excerptLength = 200,
    ) {}

    public function search(string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $query = trim($query);
        if ($query === '') {
            return new Paginator([], 0, $perPage, $page);
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = ($this->modelClass)::search($query)->paginate(perPage: $perPage, page: $page);

        $results = (new Collection($paginator->items()))->map(function (object $model) use ($query): SearchResultData {
            $row = $model->toArray();
            $title = (string) ($row['title'] ?? '');
            $excerptRaw = (string) ($row['excerpt'] ?? $row['body'] ?? '');

            return new SearchResultData(
                title: $title,
                url: '/' . ltrim((string) ($row[$this->urlColumn] ?? ''), '/'),
                excerpt: mb_strlen($excerptRaw) > $this->excerptLength
                    ? rtrim(mb_substr($excerptRaw, 0, $this->excerptLength)) . '...'
                    : $excerptRaw,
                type: (string) ($row[$this->typeColumn] ?? 'page'),
                score: (float) substr_count(mb_strtolower($title . ' ' . $excerptRaw), mb_strtolower($query)),
            );
        });

        return new Paginator($results, $paginator->total(), $perPage, $page);
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
}
