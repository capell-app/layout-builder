<?php

declare(strict_types=1);

use Capell\Core\Models\Layout as CoreLayout;
use Capell\Core\Models\Widget as CoreWidget;
use Capell\Core\Models\WidgetAsset as CoreWidgetAsset;
use Capell\LayoutBuilder\Models\Layout;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;

it('exposes package models as compatible extensions of the core models', function (): void {
    expect(Layout::class)->toExtend(CoreLayout::class)
        ->and(Widget::class)->toExtend(CoreWidget::class)
        ->and(WidgetAsset::class)->toExtend(CoreWidgetAsset::class);
});

it('keeps the existing layout builder table names', function (): void {
    expect((new Layout)->getTable())->toBe('layouts')
        ->and((new Widget)->getTable())->toBe('widgets')
        ->and((new WidgetAsset)->getTable())->toBe('layout_module_assets');
});
