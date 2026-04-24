<?php

declare(strict_types=1);

use Capell\Core\Contracts\Navigation\NavigationPageSyncer;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Str;

it('is bound in the container when the navigation package is loaded', function (): void {
    expect(app()->bound(NavigationPageSyncer::class))->toBeTrue();
});

it('removes a page from all navigations when the page is deleted via the syncer', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->create(['site_id' => $site->id]);

    $navigation = Navigation::factory()->create([
        'site_id' => $site->id,
        'items' => [
            (string) Str::uuid() => [
                'label' => 'Page link',
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'site_id' => $site->id,
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => [],
            ],
        ],
    ]);

    app(NavigationPageSyncer::class)->removePageFromAllNavigations($page);

    $navigation->refresh();

    $containsPage = collect($navigation->items)->contains(
        fn (mixed $item): bool => isset($item->data['pageable_id'])
            && (int) $item->data['pageable_id'] === $page->getKey(),
    );

    expect($containsPage)->toBeFalse();
});
