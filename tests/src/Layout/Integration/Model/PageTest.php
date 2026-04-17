<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

it('has many widget assets', function (): void {
    $page = Page::factory()->create();
    $widgetAsset = WidgetAsset::factory()->page($page)->create();

    expect($page->widgetAssets->pluck('id'))->toContain($widgetAsset->id);
});

it('has many widgets', function (): void {
    Page::factory()->create();
    $page = Page::factory()->create();
    $widget = Widget::factory()->create();
    WidgetAsset::factory()->create(['asset_id' => $page->id, 'asset_type' => 'page', 'widget_id' => $widget->id]);

    expect($page->widgets)
        ->toBeInstanceOf(EloquentCollection::class)
        ->toHaveCount(1)
        ->and($page->widgets->pluck('id'))->toContain($widget->id);
});

it('has many contents through widget assets', function (): void {
    $page = Page::factory()->create();
    $content = Collection::factory()->create();
    WidgetAsset::factory()->asset($content)->page($page)->create();

    expect($page->contents->pluck('id')->toArray())->toContain($content->id);
});

it('returns empty collections when no relations exist', function (): void {
    $page = Page::factory()->create();

    expect($page->widgetAssets)->toBeEmpty();
    expect($page->widgets)->toBeEmpty();
    expect($page->contents)->toBeEmpty();
});

it('has correct relation types', function (): void {
    $page = Page::factory()->create();

    expect($page->widgetAssets())->toBeInstanceOf(MorphMany::class);
    expect($page->widgets())->toBeInstanceOf(MorphToMany::class);
    expect($page->contents())->toBeInstanceOf(HasManyThrough::class);
});
