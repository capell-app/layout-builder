<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;

it('has many widget assets', function (): void {
    $widget = Widget::factory()->create();
    $widgetAsset = WidgetAsset::factory()->create(['widget_id' => $widget->id]);

    expect($widget->widgetAssets->pluck('id'))->toContain($widgetAsset->id);
});

it('has many pages through widget assets', function (): void {
    $widget = Widget::factory()->create();
    $page = Page::factory()->create();

    WidgetAsset::factory()->widget($widget)->asset($page)->create();

    expect($widget->pages->pluck('id'))->toContain($page->id);
});

it('has many contents through widget assets', function (): void {
    $widget = Widget::factory()->create();
    $content = Content::factory()->create();

    WidgetAsset::factory()->widget($widget)->asset($content)->create();

    expect($widget->contents->pluck('id'))->toContain($content->id);
});

it('can scope sorted', function (): void {
    Widget::factory()->create(['name' => 'B', 'order' => 2]);
    Widget::factory()->create(['name' => 'A', 'order' => 1]);
    Widget::factory()->create(['name' => 'C', 'order' => 3]);

    $result = Widget::query()->ordered()->pluck('name')->toArray();

    expect($result)->toBe(['A', 'B', 'C']);
});
