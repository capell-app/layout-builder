<?php

declare(strict_types=1);

use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Support\Cache\PageCacheService;
use Capell\Diagnostics\Actions\Dashboard\BuildCacheHealthAction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    // Bind a fake in-memory page_cache disk so file operations don't touch real storage.
    Storage::fake('page_cache');

    $this->app->bind(PageCacheService::class, fn (): PageCacheService => new PageCacheService(Storage::getFacadeRoot()));
});

it('counts enabled urls for the given site only', function (): void {
    $siteA = Site::factory()->withTranslations()->create(['name' => 'Site A']);
    $siteB = Site::factory()->withTranslations()->create(['name' => 'Site B']);

    // 2 enabled URLs in site A
    PageUrl::factory()->count(2)->create(['site_id' => $siteA->id, 'status' => true]);
    // 1 disabled URL in site A — must not be counted
    PageUrl::factory()->create(['site_id' => $siteA->id, 'status' => false]);
    // 3 enabled URLs in site B — must not be counted
    PageUrl::factory()->count(3)->create(['site_id' => $siteB->id, 'status' => true]);

    $result = BuildCacheHealthAction::run($siteA);

    expect($result->totalEnabledUrls)->toBe(2)
        ->and($result->siteId)->toBe($siteA->id)
        ->and($result->siteName)->toBe('Site A');
});

it('distinguishes cached / stale / missing', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $pageUrlCached = PageUrl::factory()->create([
        'site_id' => $site->id,
        'status' => true,
        'updated_at' => now()->subMinutes(10),
    ]);

    $pageUrlStale = PageUrl::factory()->create([
        'site_id' => $site->id,
        'status' => true,
        'updated_at' => now()->subMinutes(2),
    ]);

    $pageUrlMissing = PageUrl::factory()->create([
        'site_id' => $site->id,
        'status' => true,
    ]);

    // Put a cache file for the "cached" URL — last modified 5 min ago (before updated_at 2 min ago would be stale, but cached url was updated 10 min ago so cache is fresh)
    $cachedFile = $pageUrlCached->page_cache_file;
    if ($cachedFile !== null) {
        Storage::disk('page_cache')->put($cachedFile, 'cached content');
        // Touch to 5 minutes ago — newer than page updated_at (10 min ago) => cached
        touch(Storage::disk('page_cache')->path($cachedFile), now()->subMinutes(5)->getTimestamp());
    }

    // Put a cache file for the "stale" URL — last modified 5 min ago, but page was updated 2 min ago => stale
    $staleFile = $pageUrlStale->page_cache_file;
    if ($staleFile !== null) {
        Storage::disk('page_cache')->put($staleFile, 'stale content');
        touch(Storage::disk('page_cache')->path($staleFile), now()->subMinutes(5)->getTimestamp());
    }

    // "missing" URL has no cache file at all

    $result = BuildCacheHealthAction::run($site);

    expect($result->totalEnabledUrls)->toBe(3)
        ->and($result->cachedCount)->toBe(1)
        ->and($result->staleCount)->toBe(1)
        ->and($result->missingCount)->toBe(1);
});

it('dashboard-dashboard_reports zero counts for a site with no enabled urls', function (): void {
    $site = Site::factory()->withTranslations()->create();

    // Only a disabled URL
    PageUrl::factory()->create(['site_id' => $site->id, 'status' => false]);

    $result = BuildCacheHealthAction::run($site);

    expect($result->totalEnabledUrls)->toBe(0)
        ->and($result->cachedCount)->toBe(0)
        ->and($result->staleCount)->toBe(0)
        ->and($result->missingCount)->toBe(0)
        ->and($result->lastWarmedAt)->toBeNull();
});

it('dashboard-dashboard_reports lastWarmedAt as the newest file timestamp', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $pageUrlA = PageUrl::factory()->create([
        'site_id' => $site->id,
        'status' => true,
        'updated_at' => now()->subHour(),
    ]);

    $pageUrlB = PageUrl::factory()->create([
        'site_id' => $site->id,
        'status' => true,
        'updated_at' => now()->subHour(),
    ]);

    $olderTime = now()->subMinutes(30)->getTimestamp();
    $newerTime = now()->subMinutes(10)->getTimestamp();

    $fileA = $pageUrlA->page_cache_file;
    $fileB = $pageUrlB->page_cache_file;

    if ($fileA !== null) {
        Storage::disk('page_cache')->put($fileA, 'a');
        touch(Storage::disk('page_cache')->path($fileA), $olderTime);
    }

    if ($fileB !== null) {
        Storage::disk('page_cache')->put($fileB, 'b');
        touch(Storage::disk('page_cache')->path($fileB), $newerTime);
    }

    $result = BuildCacheHealthAction::run($site);

    $expectedIso = CarbonImmutable::createFromTimestamp($newerTime)->toIso8601String();

    expect($result->lastWarmedAt)->toBe($expectedIso);
});
