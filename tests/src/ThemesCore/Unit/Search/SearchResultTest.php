<?php

declare(strict_types=1);

use Capell\Themes\Core\Search\SearchResult;

test('search result is serialisable to array', function (): void {
    $r = new SearchResult('Hello', '/hello', 'World', 'post', 0.5);

    expect($r->toArray())->toBe([
        'title' => 'Hello',
        'url' => '/hello',
        'excerpt' => 'World',
        'type' => 'post',
        'score' => 0.5,
    ]);
});
