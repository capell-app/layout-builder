<?php

declare(strict_types=1);

use Capell\Layout\Filament\Resources\Contents\Pages\EditContent;
use Capell\Layout\Filament\Resources\Contents\RelationManagers\WidgetsRelationManager;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;

use function Pest\Livewire\livewire;

it('can list widgets for a content model', function (): void {
    $content = Content::factory()->create();

    $widget = Widget::factory()
        ->has(
            WidgetAsset::factory([
                'asset_type' => 'content',
                'asset_id' => $content->getKey(),
            ]),
            'assets',
        )
        ->create();

    Widget::factory()
        ->has(
            WidgetAsset::factory([
                'asset_type' => 'content',
                'asset_id' => $content->getKey(),
            ]),
            'assets',
        )
        ->create();

    $widgetAsset = $widget->assets()->first();

    livewire(WidgetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditContent::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2)
        ->assertCanSeeTableRecords($content->widgets)
        ->assertTableColumnStateSet('widget.name', [$widget->name], record: $widgetAsset);
});

it('can search widgets for a content model', function (): void {
    $content = Content::factory()->create();

    Widget::factory()
        ->count(5)
        ->has(WidgetAsset::factory()->state(['asset_type' => 'content', 'asset_id' => $content->getKey()]), 'assets')
        ->create();

    $widgetAssets = $content->widgets()->with('widget')->get();

    $widgetAsset = $widgetAssets->random();

    livewire(WidgetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditContent::class,
    ])
        ->assertSuccessful()
        ->searchTable($widgetAsset->widget->key)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$widgetAsset]);
});
