<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\SiteDiscovery\Data\SitemapPageData;
use Capell\SiteDiscovery\Support\Sitemap\SitemapChainBuilder;
use Capell\SiteDiscovery\Tests\SiteDiscoveryTestCase;
use Illuminate\Support\Collection;

uses(SiteDiscoveryTestCase::class);

it('builds a single node without ancestors', function (): void {
    $page = Page::factory()
        ->published()
        ->withTranslations()
        ->create([
            'parent_id' => null,
            'meta' => [
                'cache_time' => 'daily',
                'priority' => 0.8,
            ],
        ]);

    $page->refresh();
    $page->load(['translation', 'pageUrl']);

    /** @var Collection<int, SitemapPageData> $children */
    $children = new Collection([
        new SitemapPageData(label: 'Child 1', url: '/child-1'),
        new SitemapPageData(label: 'Child 2', url: '/child-2'),
    ]);

    $result = SitemapChainBuilder::build($page, $children);

    expect($result)->toBeInstanceOf(SitemapPageData::class)
        ->pageId->toBe($page->id)
        ->label->toBe($page->translation->label)
        ->url->toBe($page->pageUrl->full_url)
        ->changeFrequency->toBe('daily')
        ->priority->toBe(0.8)
        ->editUrl->toBeNull()
        ->children->toHaveCount(2)
        ->and($result->lastModified)->not->toBeNull();
});

it('builds a chain with a single ancestor', function (): void {
    $parent = Page::factory()
        ->published()
        ->withTranslations()
        ->create([
            'parent_id' => null,
            'name' => 'Parent Page',
        ]);

    $parent->refresh();
    $parent->load(['translation', 'pageUrl']);

    $child = Page::factory()
        ->published()
        ->parent($parent)
        ->withTranslations()
        ->create(['name' => 'Child Page']);

    $child->refresh();
    $child->load(['translation', 'pageUrl', 'parent.translation', 'parent.pageUrl']);

    /** @var Collection<int, SitemapPageData> $grandchildren */
    $grandchildren = new Collection([
        new SitemapPageData(label: 'Grandchild 1', url: '/grandchild-1'),
    ]);

    $result = SitemapChainBuilder::build($child, $grandchildren);

    expect($result)->toBeInstanceOf(SitemapPageData::class)
        ->pageId->toBe($parent->id)
        ->label->toBe($parent->translation->label)
        ->url->toBe($parent->pageUrl->full_url)
        ->children->toHaveCount(1);

    $firstChild = $result->children->first();

    expect($firstChild)->toBeInstanceOf(SitemapPageData::class)
        ->pageId->toBe($child->id)
        ->label->toBe($child->translation->label)
        ->url->toBe($child->pageUrl->full_url)
        ->children->toHaveCount(1);

    $grandchild = $firstChild->children->first();

    expect($grandchild)->toBeInstanceOf(SitemapPageData::class)
        ->label->toBe('Grandchild 1')
        ->url->toBe('/grandchild-1');
});

it('builds a chain with multiple ancestors', function (): void {
    $grandparent = Page::factory()
        ->published()
        ->withTranslations()
        ->create([
            'parent_id' => null,
            'name' => 'Grandparent Page',
        ]);

    $grandparent->refresh();
    $grandparent->load(['translation', 'pageUrl']);

    $parent = Page::factory()
        ->published()
        ->parent($grandparent)
        ->withTranslations()
        ->create(['name' => 'Parent Page']);

    $parent->refresh();
    $parent->load(['translation', 'pageUrl', 'parent']);

    $child = Page::factory()
        ->published()
        ->parent($parent)
        ->withTranslations()
        ->create(['name' => 'Child Page']);

    $child->refresh();
    $child->load(['translation', 'pageUrl', 'parent.translation', 'parent.pageUrl', 'parent.parent.translation', 'parent.parent.pageUrl']);

    /** @var Collection<int, SitemapPageData> $children */
    $children = new Collection([
        new SitemapPageData(label: 'Leaf Item', url: '/leaf'),
    ]);

    $result = SitemapChainBuilder::build($child, $children);

    expect($result)->toBeInstanceOf(SitemapPageData::class)
        ->pageId->toBe($grandparent->id)
        ->label->toBe($grandparent->translation->label)
        ->children->toHaveCount(1);

    $firstChild = $result->children->first();

    expect($firstChild)->toBeInstanceOf(SitemapPageData::class)
        ->pageId->toBe($parent->id)
        ->label->toBe($parent->translation->label)
        ->children->toHaveCount(1);

    $secondChild = $firstChild->children->first();

    expect($secondChild)->toBeInstanceOf(SitemapPageData::class)
        ->pageId->toBe($child->id)
        ->label->toBe($child->translation->label)
        ->children->toHaveCount(1);

    $leaf = $secondChild->children->first();

    expect($leaf)->toBeInstanceOf(SitemapPageData::class)
        ->label->toBe('Leaf Item')
        ->url->toBe('/leaf');
});

it('uses default values when meta is not set', function (): void {
    $page = Page::factory()
        ->published()
        ->withTranslations()
        ->create([
            'parent_id' => null,
            'meta' => [],
        ]);

    $page->refresh();
    $page->load(['translation', 'pageUrl']);

    $result = SitemapChainBuilder::build($page);

    expect($result)->changeFrequency->toBe('always')
        ->priority->toBe(0.5);
});

it('uses updated_at for lastModified when available', function (): void {
    $publishDate = now()->subDays(5);

    $page = Page::factory()
        ->published()
        ->withTranslations()
        ->create([
            'parent_id' => null,
            'visible_from' => $publishDate,
        ]);

    $page->refresh();
    $page->load(['translation', 'pageUrl']);

    $result = SitemapChainBuilder::build($page);

    expect($result->lastModified->toAtomString())->toBe($publishDate->toAtomString());
});

it('falls back to timestamps for lastModified when publish dates are null', function (): void {
    $createdAt = now()->subDays(10);

    $page = Page::factory()
        ->published()
        ->withTranslations()
        ->create([
            'parent_id' => null,
            'created_at' => $createdAt,
            'visible_from' => null,
        ]);

    $page->refresh();
    $page->load(['translation', 'pageUrl']);

    $result = SitemapChainBuilder::build($page);

    expect($result->lastModified->toAtomString())->toBe($page->updated_at->toAtomString());
});

it('preserves meta values through ancestor chain', function (): void {
    $parent = Page::factory()
        ->published()
        ->withTranslations()
        ->create([
            'parent_id' => null,
            'meta' => [
                'cache_time' => 'weekly',
                'priority' => 0.9,
            ],
        ]);

    $parent->refresh();
    $parent->load(['translation', 'pageUrl']);

    $child = Page::factory()
        ->published()
        ->parent($parent)
        ->withTranslations()
        ->create([
            'meta' => [
                'cache_time' => 'hourly',
                'priority' => 0.7,
            ],
        ]);

    $child->refresh();
    $child->load(['translation', 'pageUrl', 'parent.translation', 'parent.pageUrl']);

    $result = SitemapChainBuilder::build($child);

    expect($result)->changeFrequency->toBe('weekly')
        ->priority->toBe(0.9);

    $firstChild = $result->children->first();

    expect($firstChild)->changeFrequency->toBe('hourly')
        ->priority->toBe(0.7);
});

it('handles empty children array', function (): void {
    $page = Page::factory()
        ->published()
        ->withTranslations()
        ->create(['parent_id' => null]);

    $page->refresh();
    $page->load(['translation', 'pageUrl']);

    $result = SitemapChainBuilder::build($page);

    expect($result->children)->toBeNull();
});
