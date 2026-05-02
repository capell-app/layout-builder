<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Navigation\Actions\AddPageToNavigationAction;
use Capell\Navigation\Models\Navigation;
use Spatie\LaravelData\DataCollection;

it('adds a page to navigation structure', function (): void {
    $page = Page::factory()->create();
    $navigation = Navigation::factory()->defaultItems(3)->create();

    AddPageToNavigationAction::run($page, $navigation);

    $navigation->refresh();

    expect($navigation->items)->toBeInstanceOf(DataCollection::class)->toHaveCount(4);
});
