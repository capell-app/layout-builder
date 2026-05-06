<?php

declare(strict_types=1);

use Capell\Search\Contracts\Search;
use Capell\Search\Data\SearchResultData;
use Capell\Search\Http\Controllers\SearchController;
use Capell\Search\Providers\SearchServiceProvider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    app()->register(SearchServiceProvider::class);
    config()->set('capell-search.results_per_page', 5);
    config()->set('capell-search.minimum_query_length', 2);
});

test('controller returns the search page view with an empty paginator for a blank query', function (): void {
    app()->instance(Search::class, new class implements Search
    {
        public function search(string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator
        {
            throw new RuntimeException('Search should not run for a blank query.');
        }

        public function highlight(string $text, string $query): string
        {
            return $text;
        }
    });

    $request = Request::create('/search', Symfony\Component\HttpFoundation\Request::METHOD_GET, ['q' => '   ']);
    $view = (new SearchController)($request);

    expect($view->name())->toBe('capell-search::pages.search');
    expect($view->getData()['query'])->toBe('   ');
    expect($view->getData()['results'])->toBeInstanceOf(LengthAwarePaginator::class);
    expect($view->getData()['results']->total())->toBe(0);
});

test('controller passes normalized valid searches to the site search service', function (): void {
    $recordedSearch = new stdClass;
    $recordedSearch->queries = [];

    app()->instance(Search::class, new class($recordedSearch) implements Search
    {
        public function __construct(private readonly stdClass $recordedSearch) {}

        public function search(string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator
        {
            $this->recordedSearch->queries[] = $query;

            $results = new Collection([
                new SearchResultData(
                    title: 'Laravel Search',
                    url: '/laravel-search',
                    excerpt: 'Search package result',
                ),
            ]);

            return new Paginator($results, 1, $perPage, $page);
        }

        public function highlight(string $text, string $query): string
        {
            return $text;
        }
    });

    $request = Request::create('/search', Symfony\Component\HttpFoundation\Request::METHOD_GET, ['q' => '  Laravel   Search  ', 'page' => '2']);
    $view = (new SearchController)($request);

    expect($recordedSearch->queries)->toBe(['laravel search']);
    expect($view->getData()['results']->total())->toBe(1);
    expect($view->getData()['results']->currentPage())->toBe(2);
    expect($view->getData()['results']->perPage())->toBe(5);
});
