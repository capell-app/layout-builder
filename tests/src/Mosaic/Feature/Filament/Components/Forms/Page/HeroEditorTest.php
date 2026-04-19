<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Models\Page;
use Capell\Mosaic\Database\Factories\WidgetAssetFactory;
use Capell\Mosaic\Database\Factories\WidgetFactory;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('hero-editor');

test('hero editor renders on page edit form', function (): void {
    test()->actingAsAdmin();

    $page = Page::factory()->create();

    get(PageResource::getUrl('edit', ['record' => $page]))
        ->assertOk();
});

test('hero editor is visible when page has no hero widget assets', function (): void {
    test()->actingAsAdmin();

    $page = Page::factory()->create();
    $cacheKey = sprintf('page-%d-has-hero-widget-assets', $page->id);

    cache()->forget($cacheKey);

    $response = get(PageResource::getUrl('edit', ['record' => $page]));

    $response->assertOk();
    expect(cache()->has($cacheKey))->toBeFalse();
});

test('hero editor is hidden when page has hero widget assets', function (): void {
    test()->actingAsAdmin();

    $page = Page::factory()->create();

    $heroWidget = WidgetFactory::new()
        ->state(['key' => 'hero-banner'])
        ->create();

    WidgetAssetFactory::new()
        ->state([
            'widget_id' => $heroWidget->id,
            'pageable_type' => $page->getMorphClass(),
            'pageable_id' => $page->id,
        ])
        ->create();

    get(PageResource::getUrl('edit', ['record' => $page]))
        ->assertOk();
});

test('hero editor caches hero asset existence checks per page', function (): void {
    test()->actingAsAdmin();

    $page = Page::factory()->create();
    $cacheKey = sprintf('page-%d-has-hero-widget-assets', $page->id);

    cache()->forget($cacheKey);

    get(PageResource::getUrl('edit', ['record' => $page]));
    $firstCacheState = cache()->has($cacheKey);

    $page2 = Page::factory()->create();
    $cacheKey2 = sprintf('page-%d-has-hero-widget-assets', $page2->id);

    cache()->forget($cacheKey2);

    get(PageResource::getUrl('edit', ['record' => $page2]));
    $secondCacheState = cache()->has($cacheKey2);

    expect($firstCacheState)->toBe($secondCacheState);
});
