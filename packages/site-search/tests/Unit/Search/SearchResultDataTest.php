<?php

declare(strict_types=1);

use Capell\SiteSearch\Data\SearchResultData;

test('search result data is serialisable to array', function (): void {
    $result = new SearchResultData('Hello', '/hello', 'World', 'post', 0.5);

    expect($result->toArray())->toBe([
        'title' => 'Hello',
        'url' => '/hello',
        'excerpt' => 'World',
        'type' => 'post',
        'score' => 0.5,
    ]);
});
