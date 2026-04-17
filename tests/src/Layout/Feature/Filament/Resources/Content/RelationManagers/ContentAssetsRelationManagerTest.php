<?php

declare(strict_types=1);

use Capell\Core\Models\AssetRelation;
use Capell\Core\Models\Page;
use Capell\Layout\Enums\AssetEnum;
use Capell\Layout\Filament\Resources\Contents\Pages\EditContent;
use Capell\Layout\Filament\Resources\Contents\RelationManagers\ContentAssetsRelationManager;
use Capell\Layout\Models\Collection;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('can list content assets', function (): void {
    $content = Collection::factory()
        ->has(AssetRelation::factory(['related_type' => AssetEnum::Content->value])->count(5), 'assets')
        ->create();

    $resource = $content->assets->first()->load('asset');

    livewire(ContentAssetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditContent::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($content->assets)
        ->assertTableColumnStateSet('asset.name', state: [$resource->asset->name], record: $resource);
});

it('can search content assets', function (): void {
    $content = Collection::factory()
        ->has(
            AssetRelation::factory(['related_type' => AssetEnum::Content->value])
                ->asset(
                    Capell\Core\Enums\AssetEnum::Page,
                    ['name' => 'First'],
                ),
            'assets',
        )
        ->has(
            AssetRelation::factory([
                'related_type' => AssetEnum::Content->value,
                'asset_type' => AssetEnum::Content->value,
                'asset_id' => Collection::factory(['name' => 'Second']),
            ]),
            'assets',
        )
        ->has(
            AssetRelation::factory(['related_type' => AssetEnum::Content->value])
                ->asset(
                    Capell\Core\Enums\AssetEnum::Page,
                    ['name' => 'First'],
                ),
            'assets',
        )
        ->has(
            AssetRelation::factory([
                'related_type' => AssetEnum::Content->value,
                'asset_type' => AssetEnum::Content->value,
                'asset_id' => Collection::factory(['name' => 'Fourth']),
            ]),
            'assets',
        )
        ->create();

    $resource = $content->assets->first()->load('asset');

    livewire(ContentAssetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditContent::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(4)
        ->searchTable($resource->asset->name)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$resource]);
});

test('can create a asset for a widget', function (string $assetType): void {
    $content = Collection::factory()->create();

    $action = TestAction::make(CreateAction::class)->table();

    $asset = match ($assetType) {
        'content' => Collection::factory()->create(),
        'page' => Page::factory()->create(),
    };

    livewire(ContentAssetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => EditContent::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0)
        ->assertActionExists($action)
        ->mountAction($action)
        ->fillForm([
            'asset_type' => $asset->getMorphClass(),
            'asset_id' => [$asset->getKey()],
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    assertDatabaseHas(AssetRelation::class, [
        'related_type' => $content->getMorphClass(),
        'related_id' => $content->id,
        'asset_type' => $assetType,
    ]);
})
    ->with(['page', 'content']);
