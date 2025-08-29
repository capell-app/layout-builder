<?php

declare(strict_types=1);

use Capell\Core\Models;
use Capell\Layout\Filament\Resources\Contents\ContentResource;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\ContentAsset;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('can list content assets', function (): void {
    $content = Content::factory()
        ->has(ContentAsset::factory()->count(5), 'assets')
        ->create();

    $resource = $content->assets->first()->load('asset');

    livewire(ContentResource\RelationManagers\ContentAssetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => ContentResource\Pages\EditContent::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($content->assets)
        ->assertTableColumnStateSet('asset.name', state: [$resource->asset->name], record: $resource);
});

it('can search content assets', function (): void {
    $content = Content::factory()
        ->has(ContentAsset::factory()->page(['name' => 'First']), 'assets')
        ->has(ContentAsset::factory()->content(['name' => 'Second']), 'assets')
        ->has(ContentAsset::factory()->page(['name' => 'Third']), 'assets')
        ->has(ContentAsset::factory()->content(['name' => 'Fourth']), 'assets')
        ->create();

    $resource = $content->assets->first()->load('asset');

    livewire(ContentResource\RelationManagers\ContentAssetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => ContentResource\Pages\EditContent::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->searchTable($resource->asset->name)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$resource]);
});

test('can create a asset for a widget', function (string $assetType): void {
    $content = Content::factory()->create();

    $action = TestAction::make(CreateAction::class)->table();

    livewire(ContentResource\RelationManagers\ContentAssetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => ContentResource\Pages\EditContent::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0)
        ->assertActionExists($action)
        ->mountAction($action)
        ->fillForm(
            match ($assetType) {
                'content' => [
                    'asset_type' => app(Content::class)->getMorphClass(),
                    'asset_id' => [
                        (string) Content::factory()->create()->id,
                    ],
                ],
                'page' => [
                    'asset_type' => app(Models\Page::class)->getMorphClass(),
                    'asset_id' => [
                        (string) Models\Page::factory()->create()->id,
                    ],
                ],
            },
        )
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    assertDatabaseHas(ContentAsset::class, [
        'content_id' => $content->id,
        'asset_type' => $assetType,
    ]);
})->with(['page', 'content']);
