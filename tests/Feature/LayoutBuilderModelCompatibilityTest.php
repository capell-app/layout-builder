<?php

declare(strict_types=1);

use Capell\Core\Models\Layout as CoreLayout;
use Capell\LayoutBuilder\Models\Layout;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Models\WidgetWidget;

it('keeps layouts on the core layout model and uses package-owned widget models', function (): void {
    expect(Layout::class)->toExtend(CoreLayout::class)
        ->and(Widget::class)->not->toExtend(CoreLayout::class)
        ->and(WidgetAsset::class)->not->toExtend(CoreLayout::class)
        ->and(WidgetWidget::class)->not->toExtend(CoreLayout::class);
});

it('keeps the existing layout builder table names', function (): void {
    expect((new Layout)->getTable())->toBe('layouts')
        ->and((new Widget)->getTable())->toBe('widgets')
        ->and((new WidgetAsset)->getTable())->toBe('widget_assets')
        ->and((new WidgetWidget)->getTable())->toBe('widget_widgets');
});
