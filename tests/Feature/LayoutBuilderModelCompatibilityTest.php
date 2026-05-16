<?php

declare(strict_types=1);

use Capell\Core\Models\Layout as CoreLayout;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Capell\LayoutBuilder\Models\Layout;

it('keeps layouts on the core layout model and uses package-owned element models', function (): void {
    expect(Layout::class)->toExtend(CoreLayout::class)
        ->and(Element::class)->not->toExtend(CoreLayout::class)
        ->and(ElementAsset::class)->not->toExtend(CoreLayout::class);
});

it('keeps the existing layout builder table names', function (): void {
    expect((new Layout)->getTable())->toBe('layouts')
        ->and((new Element)->getTable())->toBe('elements')
        ->and((new ElementAsset)->getTable())->toBe('layout_element_assets');
});
