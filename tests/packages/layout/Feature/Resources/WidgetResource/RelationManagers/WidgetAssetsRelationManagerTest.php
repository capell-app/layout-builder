<?php

declare(strict_types=1);

use Capell\Core\Models;
use Capell\Layout\Filament\Resources\WidgetResource;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Filament\Tables\Actions\CreateAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('can list assets for a widget', function (): void {
    $widget = Widget::factory()
        ->has(WidgetAsset::factory()->count(5), 'assets')
        ->create();

    $resource = $widget->assets->first();
    $resource->load('asset');

    livewire(WidgetResource\RelationManagers\WidgetAssetsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => WidgetResource\Pages\EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($widget->assets)
        ->assertTableColumnStateSet('asset.name', [$resource->asset->name], record: $resource);
});

test('can create a asset for a widget', function (string $assetType): void {
    $widget = Widget::factory()->create();

    livewire(WidgetResource\RelationManagers\WidgetAssetsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => WidgetResource\Pages\EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0)
        ->assertSchemaStateSet(CreateAction::class)
        ->fillForm(
            match ($assetType) {
                'content' => [
                    'asset_type' => app(Content::class)->getMorphClass(),
                    'assets' => [
                        (string) Content::factory()->create()->id,
                    ],
                ],
                'media' => [
                    'asset_type' => app(Models\Media::class)->getMorphClass(),
                    'assets' => [
                        (string) Models\Media::factory()->create()->id,
                    ],
                ],
                'page' => [
                    'asset_type' => app(Models\Page::class)->getMorphClass(),
                    'assets' => [
                        (string) Models\Page::factory()->create()->id,
                    ],
                ],
            },
            formName: 'mountedTableActionForm'
        )
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors()
        ->assertCountTableRecords(1);

    assertDatabaseHas(WidgetAsset::class, [
        'widget_id' => $widget->id,
        'asset_type' => $assetType,
    ]);
})->with(['page', 'media', 'content']);
