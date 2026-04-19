<?php

declare(strict_types=1);

use Capell\Themes\Core\Search\DatabaseSiteSearch;

test('returns empty paginator for empty query', function (): void {
    $db = Mockery::mock(Illuminate\Database\ConnectionInterface::class);

    $search = new DatabaseSiteSearch($db);
    $results = $search->search('   ');

    expect($results->total())->toBe(0);
    expect($results->isEmpty())->toBeTrue();
});

test('wraps matches in <mark> tags with escaping', function (): void {
    $db = Mockery::mock(Illuminate\Database\ConnectionInterface::class);
    $search = new DatabaseSiteSearch($db);

    $html = $search->highlight('<b>Laravel is great</b> for sites', 'Laravel');

    expect($html)
        ->toContain('<mark>Laravel</mark>')
        ->toContain('&lt;b&gt;');
});
