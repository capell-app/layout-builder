<?php

declare(strict_types=1);

use Capell\Search\Contracts\Search;
use Capell\Search\Drivers\ScoutSearch;
use Illuminate\Pagination\LengthAwarePaginator;

test('ScoutSearch implements Search', function (): void {
    expect(ScoutSearch::class)->toImplement(Search::class);
});

test('search returns empty paginator for blank query', function (): void {
    $search = new ScoutSearch('App\\Models\\Page', 'slug');
    $results = $search->search('   ');

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($results->total())->toBe(0);
    expect($results->isEmpty())->toBeTrue();
});

test('highlight wraps keyword in mark tags', function (): void {
    $search = new ScoutSearch('App\\Models\\Page', 'slug');
    $html = $search->highlight('<b>Meilisearch</b> is fast', 'Meilisearch');

    expect($html)->toContain('<mark>Meilisearch</mark>');
    expect($html)->toContain('&lt;b&gt;');
});

test('highlight returns escaped text when query is empty', function (): void {
    $search = new ScoutSearch('App\\Models\\Page', 'slug');
    $html = $search->highlight('<script>alert(1)</script>', '');

    expect($html)->toContain('&lt;script&gt;');
    expect($html)->not->toContain('<mark>');
});
