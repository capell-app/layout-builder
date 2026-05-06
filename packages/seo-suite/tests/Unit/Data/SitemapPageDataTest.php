<?php

declare(strict_types=1);

use Capell\SeoSuite\Data\SitemapPageData;
use Carbon\CarbonImmutable;

it('serializes last modified dates with atom formatting', function (): void {
    $lastModified = CarbonImmutable::parse('2026-03-15 09:30:00', 'UTC');

    $data = new SitemapPageData(
        label: 'Example page',
        url: 'https://example.test/example-page',
        lastModified: $lastModified,
    );

    expect($data->lastModified)
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($data->toArray()['lastModified'])
        ->toBe($lastModified->toAtomString());
});
