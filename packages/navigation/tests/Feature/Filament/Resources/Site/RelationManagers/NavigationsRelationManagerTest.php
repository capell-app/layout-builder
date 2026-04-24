<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Sites\Pages\EditSite;
use Capell\Core\Models\Site;
use Capell\Navigation\Filament\Resources\Sites\RelationManagers\NavigationsRelationManager;
use Capell\Navigation\Models\Navigation;

use function Pest\Livewire\livewire;

it('can list navigations', function (): void {
    test()->actingAsAdmin();

    $site = Site::factory()
        ->has(Navigation::factory()->count(10))
        ->create();

    $navigation = $site->navigations->first();

    livewire(NavigationsRelationManager::class, [
        'ownerRecord' => $site,
        'pageClass' => EditSite::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(10)
        ->assertCanSeeTableRecords($site->navigations)
        ->assertTableColumnStateSet('name', [$navigation->name], record: $navigation);
});

it('can search navigations', function (): void {
    test()->actingAsAdmin();

    $site = Site::factory()
        ->has(Navigation::factory()->count(10))
        ->create();

    $navigation = $site->navigations->random();

    livewire(NavigationsRelationManager::class, [
        'ownerRecord' => $site,
        'pageClass' => EditSite::class,
    ])
        ->assertSuccessful()
        ->searchTable($navigation->name)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$navigation]);
});
