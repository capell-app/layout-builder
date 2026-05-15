<?php

declare(strict_types=1);

use Capell\Core\Models\Layout as CoreLayout;
use Capell\Core\Models\LayoutModule as CoreElement;
use Capell\Core\Models\LayoutModuleAsset as CoreElementAsset;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Capell\LayoutBuilder\Models\Layout;

it('exposes package models as compatible extensions of the core models', function (): void {
    expect(Layout::class)->toExtend(CoreLayout::class)
        ->and(Element::class)->toExtend(CoreElement::class)
        ->and(ElementAsset::class)->toExtend(CoreElementAsset::class);
});

it('keeps the existing layout builder table names', function (): void {
    expect((new Layout)->getTable())->toBe('layouts')
        ->and((new Element)->getTable())->toBe('elements')
        ->and((new ElementAsset)->getTable())->toBe('layout_element_assets');
});
