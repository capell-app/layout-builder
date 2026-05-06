<?php

declare(strict_types=1);

use Capell\SeoSuite\Support\SearchConsole\NullSearchConsoleClient;

it('is never configured and returns empty insight lists', function (): void {
    $client = new NullSearchConsoleClient;

    expect($client->isConfigured())->toBeFalse()
        ->and($client->pageInsights('https://example.com/about'))->toBe([])
        ->and($client->decliningPages(1))->toBe([])
        ->and($client->decliningPages(1, 5))->toBe([])
        ->and($client->urlMetricRows(1))->toBe([])
        ->and($client->urlMetricRows(1, 5))->toBe([]);
});
