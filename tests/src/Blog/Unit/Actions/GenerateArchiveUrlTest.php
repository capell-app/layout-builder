<?php

declare(strict_types=1);

use Capell\Blog\Actions\GenerateArchiveUrl;
use Capell\Blog\Data\ArchiveMonthData;
use Capell\Core\Models\PageUrl;

it('generates archive url with year and month', function (): void {
    $mockUrl = Mockery::mock(PageUrl::class)->makePartial();
    $mockUrl->shouldReceive('getAttribute')->with('full_url')->andReturn('https://example.com/blog');
    $date = new ArchiveMonthData(2025, 3);

    $archiveUrl = GenerateArchiveUrl::run($mockUrl, $date);

    expect($archiveUrl)->toBe('https://example.com/blog/2025-03');
});

it('handles single digit months with leading zero', function (): void {
    $mockUrl = Mockery::mock(PageUrl::class)->makePartial();
    $mockUrl->shouldReceive('getAttribute')->with('full_url')->andReturn('https://example.com/blog');
    $date = new ArchiveMonthData(2025, 1);

    $archiveUrl = GenerateArchiveUrl::run($mockUrl, $date);

    expect($archiveUrl)->toBe('https://example.com/blog/2025-01');
});
