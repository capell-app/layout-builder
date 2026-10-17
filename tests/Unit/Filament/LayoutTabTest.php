<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Filament\Components\Forms\Page\Tab\LayoutTab;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderStatefulSchemaHarness;
use Filament\Schemas\Schema;

it('only exposes the layout editor when both a page record and layout are available', function (): void {
    $page = Page::factory()->createOne();
    $harness = new LayoutBuilderStatefulSchemaHarness;
    $harness->data = ['layout_id' => $page->layout_id];
    $tab = LayoutTab::make();
    $schema = Schema::make($harness)->model(Page::class)->statePath('data')->components([$tab]);
    $schema->getComponents(withHidden: true);

    expect($tab->isVisible())->toBeFalse();

    $schema->record($page);
    expect($tab->isVisible())->toBeTrue();

    $harness->data['layout_id'] = null;
    $page->layout_id = null;
    expect($tab->isVisible())->toBeFalse();
});
