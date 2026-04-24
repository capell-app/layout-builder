<?php

declare(strict_types=1);

use Capell\Core\Models\AssetRelation;
use Capell\Core\Models\Page;
use Capell\Mosaic\Enums\AssetEnum;
use Capell\Mosaic\Filament\Resources\Sections\Pages\EditSection;
use Capell\Mosaic\Filament\Resources\Sections\RelationManagers\SectionAssetsRelationManager;
use Capell\Mosaic\Models\Section;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

it('can list section assets', function (): void {
    $section = Section::factory()
        ->has(AssetRelation::factory(['related_type' => AssetEnum::Section->value])->count(5), 'assets')
        ->create();

    $resource = $section->assets->first()->load('asset');

    livewire(SectionAssetsRelationManager::class, [
        'ownerRecord' => $section,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($section->assets)
        ->assertTableColumnStateSet('asset.name', state: [$resource->asset->name], record: $resource);
});

it('can search section assets by name', function (): void {
    $section = Section::factory()
        ->has(
            AssetRelation::factory(['related_type' => AssetEnum::Section->value])
                ->asset(
                    Capell\Core\Enums\AssetEnum::Page,
                    ['name' => 'First'],
                ),
            'assets',
        )
        ->has(
            AssetRelation::factory([
                'related_type' => AssetEnum::Section->value,
                'asset_type' => AssetEnum::Section->value,
                'asset_id' => Section::factory(['name' => 'Second']),
            ]),
            'assets',
        )
        ->has(
            AssetRelation::factory(['related_type' => AssetEnum::Section->value])
                ->asset(
                    Capell\Core\Enums\AssetEnum::Page,
                    ['name' => 'Third'],
                ),
            'assets',
        )
        ->has(
            AssetRelation::factory([
                'related_type' => AssetEnum::Section->value,
                'asset_type' => AssetEnum::Section->value,
                'asset_id' => Section::factory(['name' => 'Fourth']),
            ]),
            'assets',
        )
        ->create();

    $resource = $section->assets->first()->load('asset');

    livewire(SectionAssetsRelationManager::class, [
        'ownerRecord' => $section,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(4)
        ->searchTable($resource->asset->name)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$resource]);
});

it('returns no results when search matches nothing', function (): void {
    $section = Section::factory()
        ->has(AssetRelation::factory(['related_type' => AssetEnum::Section->value])->count(3), 'assets')
        ->create();

    livewire(SectionAssetsRelationManager::class, [
        'ownerRecord' => $section,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->searchTable('zzz-no-match')
        ->assertCountTableRecords(0);
});

it('can filter section assets by asset type', function (): void {
    $section = Section::factory()
        ->has(
            AssetRelation::factory(['related_type' => AssetEnum::Section->value])
                ->asset(Capell\Core\Enums\AssetEnum::Page),
            'assets',
        )
        ->has(
            AssetRelation::factory([
                'related_type' => AssetEnum::Section->value,
                'asset_type' => AssetEnum::Section->value,
                'asset_id' => Section::factory(),
            ]),
            'assets',
        )
        ->create();

    $pageAsset = $section->assets->firstWhere('asset_type', 'page');
    $sectionAsset = $section->assets->firstWhere('asset_type', AssetEnum::Section->value);

    livewire(SectionAssetsRelationManager::class, [
        'ownerRecord' => $section,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2)
        ->filterTable('asset_type', 'page')
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$pageAsset])
        ->assertCanNotSeeTableRecords([$sectionAsset]);
});

it('can bulk delete section assets', function (): void {
    $section = Section::factory()
        ->has(AssetRelation::factory(['related_type' => AssetEnum::Section->value])->count(3), 'assets')
        ->create();

    $assets = $section->assets;

    livewire(SectionAssetsRelationManager::class, [
        'ownerRecord' => $section,
        'pageClass' => EditSection::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->selectTableRecords($assets->pluck('id')->toArray())
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);

    foreach ($assets as $asset) {
        assertDatabaseMissing(AssetRelation::class, ['id' => $asset->id]);
    }
});

test('can create an asset for a section', function (string $assetType): void {
    $section = Section::factory()->create();

    $action = TestAction::make(CreateAction::class)->table();

    $asset = match ($assetType) {
        'section' => Section::factory()->create(),
        'page' => Page::factory()->create(),
    };

    livewire(SectionAssetsRelationManager::class, [
        'ownerRecord' => $section,
        'pageClass' => EditSection::class,
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
        'related_type' => $section->getMorphClass(),
        'related_id' => $section->id,
        'asset_type' => $assetType,
    ]);
})
    ->with(['page', 'section']);
