<?php

declare(strict_types=1);

use Capell\Blog\Actions\GenerateArchiveUrl;
use Capell\Blog\Data\ArchiveMonthData;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;

it('generates archive url with year and month', function (): void {
    $site = Site::factory()->create();
    $url = PageUrl::factory()->site($site)->create(['full_url' => 'https://example.com/blog']);
    $date = new ArchiveMonthData(2025, 3);

    $archiveUrl = GenerateArchiveUrl::run($url, $date);

    expect($archiveUrl)->toBe('https://example.com/blog/2025-03');
});

it('pads month with leading zero', function (): void {
    $site = Site::factory()->create();
    $url = PageUrl::factory()->site($site)->create(['full_url' => 'https://example.com/blog']);
    $date = new ArchiveMonthData(2025, 1);

    $archiveUrl = GenerateArchiveUrl::run($url, $date);

    expect($archiveUrl)->toBe('https://example.com/blog/2025-01');
});
