<?php

declare(strict_types=1);

// tests/Integration/Models/SiteTest.php

use Capell\Core\Models\Page;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;

it('has many widget assets', function (): void {
    $page = Page::factory()->create();
    $widgetAsset = WidgetAsset::factory()->create(['page_id' => $page->id]);

    expect($page->widgetAssets->pluck('id'))->toContain($widgetAsset->id);
});

it('has many widgets', function (): void {
    $page = Page::factory()->create();
    $widget = Widget::factory()->create();
    WidgetAsset::factory()->create(['asset_id' => $page->id, 'asset_type' => 'page', 'widget_id' => $widget->id]);

    expect($page->widgets->pluck('id')->toArray())->toContain($widget->id);
});
