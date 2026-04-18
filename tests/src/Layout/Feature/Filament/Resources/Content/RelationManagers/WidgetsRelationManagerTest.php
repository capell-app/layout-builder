<?php

declare(strict_types=1);

use Capell\Mosaic\Filament\Resources\Sections\Pages\EditSection;
use Capell\Mosaic\Filament\Resources\Sections\RelationManagers\WidgetsRelationManager;
use Capell\Mosaic\Models\Section;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;

use function Pest\Livewire\livewire;

it('can list widgets for a content model', function (): void {
    $content = Section::factory()->create();

    $widget = Widget::factory()
        ->has(
            WidgetAsset::factory()->asset($content),
            'assets',
        )
        ->create();

    Widget::factory()
        ->has(
            WidgetAsset::factory()->asset($content),
            'assets',
        )
        ->create();

    $widgetAsset = $widget->assets()->first();

    livewire(WidgetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2)
        ->assertCanSeeTableRecords($content->widgets)
        ->assertTableColumnStateSet('widget.name', [$widget->name], record: $widgetAsset);
});

it('can search widgets for a content model', function (): void {
    $content = Section::factory()->create();

    Widget::factory()
        ->count(5)
        ->has(WidgetAsset::factory()->asset($content), 'assets')
        ->create();

    $widgetAssets = $content->widgets()->with('widget')->get();

    $widgetAsset = $widgetAssets->random();

    livewire(WidgetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->searchTable($widgetAsset->widget->key)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$widgetAsset]);
});
