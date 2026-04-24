<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\RemovePageFromNavigationAction;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Str;

it('removes a page from navigation', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->create(['site_id' => $site->id]);

    $itemKey = (string) Str::uuid();
    $navigation = Navigation::factory()->create([
        'site_id' => $site->id,
        'items' => [
            $itemKey => [
                'label' => 'Test',
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

    RemovePageFromNavigationAction::run($page, $navigation);

    $navigation->refresh();

    $pageIds = collect($navigation->items)
        ->map(static fn (NavigationItemData $item): ?int => $item->data['pageable_id'] ?? null)
        ->all();

    expect($pageIds)->not()->toContain($page->getKey());
});

it('leaves other pages intact when removing one page', function (): void {
    $site = Site::factory()->create();
    $pageToRemove = Page::factory()->create(['site_id' => $site->id]);
    $pageToKeep = Page::factory()->create(['site_id' => $site->id]);

    $navigation = Navigation::factory()->create([
        'site_id' => $site->id,
        'items' => [
            (string) Str::uuid() => [
                'label' => 'Remove',
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'site_id' => $site->id,
                    'pageable_id' => $pageToRemove->getKey(),
                    'pageable_type' => $pageToRemove->getMorphClass(),
                ],
                'children' => [],
            ],
            (string) Str::uuid() => [
                'label' => 'Keep',
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'site_id' => $site->id,
                    'pageable_id' => $pageToKeep->getKey(),
                    'pageable_type' => $pageToKeep->getMorphClass(),
                ],
                'children' => [],
            ],
        ],
    ]);

    RemovePageFromNavigationAction::run($pageToRemove, $navigation);

    $navigation->refresh();

    $pageIds = collect($navigation->items)
        ->map(static fn (NavigationItemData $item): ?int => isset($item->data['pageable_id']) ? (int) $item->data['pageable_id'] : null)
        ->filter()
        ->values()
        ->all();

    expect($pageIds)->not()->toContain($pageToRemove->getKey())
        ->and($pageIds)->toContain($pageToKeep->getKey());
});
