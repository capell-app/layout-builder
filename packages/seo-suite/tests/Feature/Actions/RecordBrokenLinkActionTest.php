<?php

declare(strict_types=1);

use Capell\Core\Events\UrlVisitFailed;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\SeoSuite\Actions\RecordBrokenLinkAction;
use Capell\SeoSuite\Models\BrokenLink;

it('records broken links with page context', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()
        ->has(SiteDomain::factory()->language($language)->state([
            'domain' => 'example.com',
            'scheme' => 'https',
            'status' => true,
        ]))
        ->create();
    $page = Page::factory()->for($site)->create();

    RecordBrokenLinkAction::run('https://example.com/missing', 404, $page->getKey());

    $brokenLink = BrokenLink::query()
        ->where('page_id', $page->getKey())
        ->firstOrFail();

    expect($brokenLink->target_url)->toBe('https://example.com/missing')
        ->and($brokenLink->http_status)->toBe(404)
        ->and($brokenLink->last_checked_at)->not()->toBeNull();
});

it('ignores broken links without page context', function (): void {
    RecordBrokenLinkAction::run('https://example.com/missing', 404, null);

    expect(BrokenLink::query()->count())->toBe(0);
});

it('records broken links from failed url visit events', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()
        ->has(SiteDomain::factory()->language($language)->state([
            'domain' => 'example.com',
            'scheme' => 'https',
            'status' => true,
        ]))
        ->create();
    $page = Page::factory()->for($site)->create();

    event(new UrlVisitFailed('https://example.com/missing', 404, $page->getKey()));

    expect(BrokenLink::query()
        ->where('page_id', $page->getKey())
        ->where('target_url', 'https://example.com/missing')
        ->exists())->toBeTrue();
});
