<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\AddPageToNavigationAction;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

it('adds the saved page to provided navigations', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->create([
        'site_id' => $site->id,
    ]);

    $navigation = Navigation::factory()->create([
        'site_id' => $site->id,
        'items' => [],
    ]);

    (new Collection([$navigation]))->each(
        static fn (Navigation $nav): mixed => AddPageToNavigationAction::run($page, $nav),
    );

    $navigation->refresh();

    $items = $navigation->items;

    if ($items instanceof DataCollection) {
        $items = collect(iterator_to_array($items));
    }

    /** @var Collection<int, NavigationItemData> $items */
    $pageIds = $items->map(static fn (NavigationItemData $item): ?int => $item->data['pageable_id'] ?? null)->all();

    expect($pageIds)->toContain($page->getKey());
});
