<?php

declare(strict_types=1);

use Capell\SeoTools\Data\SitemapUrlItemData;
use Capell\SeoTools\Support\Sitemap\SitemapStateStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
});

it('returns an empty array when no state file exists', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');

    expect($store->load('example-com'))->toBe([]);
});

it('persists and reloads a URL→lastmod map', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');
    $map = [
        'https://example.com/page-a' => '2024-01-01T00:00:00+00:00',
        'https://example.com/page-b' => '2024-06-15T12:30:00+00:00',
    ];

    $store->save('example-com', $map);

    expect($store->load('example-com'))->toBe($map);
});

it('state file is written under the .state subdirectory', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');
    $store->save('my-domain', ['https://example.com/' => '2024-01-01T00:00:00+00:00']);

    expect(Storage::disk('local')->exists('sitemaps/.state/my-domain.json'))->toBeTrue();
});

it('delete removes the state file', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');
    $store->save('example-com', ['https://example.com/' => '2024-01-01T00:00:00+00:00']);
    $store->delete('example-com');

    expect($store->load('example-com'))->toBe([]);
});

it('delete is a no-op when no state file exists', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');

    expect(fn () => $store->delete('nonexistent-domain'))->not->toThrow(Throwable::class);
});

it('hasChanged returns false for identical maps', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');
    $map = ['https://example.com/a' => '2024-01-01T00:00:00+00:00'];

    expect($store->hasChanged($map, $map))->toBeFalse();
});

it('hasChanged returns false for empty-to-empty comparison', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');

    expect($store->hasChanged([], []))->toBeFalse();
});

it('hasChanged returns true when a URL is added', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');
    $old = ['https://example.com/a' => '2024-01-01T00:00:00+00:00'];
    $new = $old + ['https://example.com/b' => '2024-01-02T00:00:00+00:00'];

    expect($store->hasChanged($new, $old))->toBeTrue();
});

it('hasChanged returns true when a URL is removed', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');
    $old = [
        'https://example.com/a' => '2024-01-01T00:00:00+00:00',
        'https://example.com/b' => '2024-01-02T00:00:00+00:00',
    ];
    $new = ['https://example.com/a' => '2024-01-01T00:00:00+00:00'];

    expect($store->hasChanged($new, $old))->toBeTrue();
});

it('hasChanged returns true when a lastmod value changes', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');
    $old = ['https://example.com/' => '2024-01-01T00:00:00+00:00'];
    $new = ['https://example.com/' => '2024-06-01T00:00:00+00:00'];

    expect($store->hasChanged($new, $old))->toBeTrue();
});

it('hasChanged returns true when URL keys differ even with the same count', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');
    $old = ['https://example.com/old' => '2024-01-01T00:00:00+00:00'];
    $new = ['https://example.com/new' => '2024-01-01T00:00:00+00:00'];

    expect($store->hasChanged($new, $old))->toBeTrue();
});

it('buildUrlMap converts SitemapUrlItemData items to a URL→lastmod string map', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');
    $lastmod = CarbonImmutable::parse('2024-01-15T10:00:00+00:00');

    $items = [
        new SitemapUrlItemData(loc: 'https://example.com/page', lastmod: $lastmod),
        new SitemapUrlItemData(loc: 'https://example.com/other'),
    ];

    $map = $store->buildUrlMap($items);

    expect($map)
        ->toHaveKey('https://example.com/page', $lastmod->format(DATE_ATOM))
        ->toHaveKey('https://example.com/other', '');
});

it('buildUrlMap returns an empty map for an empty item list', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');

    expect($store->buildUrlMap([]))->toBe([]);
});

it('round-trip: save then load returns a stable map across domain keys', function (): void {
    $store = new SitemapStateStore('local', 'sitemaps');
    $mapA = ['https://a.com/' => '2024-01-01T00:00:00+00:00'];
    $mapB = ['https://b.com/' => '2024-02-01T00:00:00+00:00'];

    $store->save('domain-a', $mapA);
    $store->save('domain-b', $mapB);

    expect($store->load('domain-a'))->toBe($mapA)
        ->and($store->load('domain-b'))->toBe($mapB);
});
