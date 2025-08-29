<?php

declare(strict_types=1);

use Capell\Layout\Filament\Resources\Contents\ContentResource;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;

use function Pest\Livewire\livewire;

it('can list widgets for a content model', function (): void {
    $content = Content::factory()->create();

    Widget::factory()
        ->count(5)
        ->has(WidgetAsset::factory()->state(['asset_type' => 'content', 'asset_id' => $content->getKey()]), 'assets')
        ->create();

    $widget = $content->widgets->first();

    livewire(ContentResource\RelationManagers\WidgetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => ContentResource\Pages\EditContent::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($content->widgets)
        ->assertTableColumnStateSet('name', [$widget->name], record: $widget);
});

it('can search widgets for a content model', function (): void {
    $content = Content::factory()->create();

    Widget::factory()
        ->count(5)
        ->has(WidgetAsset::factory()->state(['asset_type' => 'content', 'asset_id' => $content->getKey()]), 'assets')
        ->create();

    $widget = $content->widgets->random();

    livewire(ContentResource\RelationManagers\WidgetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => ContentResource\Pages\EditContent::class,
    ])
        ->assertSuccessful()
        ->searchTable($widget->key)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$widget]);
});
