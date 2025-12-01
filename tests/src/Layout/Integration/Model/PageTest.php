<?php

declare(strict_types=1);

// tests/Integration/Models/SiteTest.php

use Capell\Core\Models\Page;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

it('has many widget assets', function (): void {
    $page = Page::factory()->create();
    $widgetAsset = WidgetAsset::factory()->create(['page_id' => $page->id]);

    expect($page->widgetAssets->pluck('id'))->toContain($widgetAsset->id);
});

it('has many widgets', function (): void {
    Page::factory()->create();
    $page = Page::factory()->create();
    $widget = Widget::factory()->create();
    WidgetAsset::factory()->create(['asset_id' => $page->id, 'asset_type' => 'page', 'widget_id' => $widget->id]);

    expect($page->widgets)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($page->widgets->pluck('id'))->toContain($widget->id);
});

it('has many contents through widget assets', function (): void {
    $page = Page::factory()->create();
    $content = Content::factory()->create();
    WidgetAsset::factory()->create(['asset_id' => $content->id, 'asset_type' => 'content', 'page_id' => $page->id]);

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

    expect($page->widgetAssets())->toBeInstanceOf(HasMany::class);
    expect($page->widgets())->toBeInstanceOf(MorphToMany::class);
    expect($page->contents())->toBeInstanceOf(HasManyThrough::class);
});
