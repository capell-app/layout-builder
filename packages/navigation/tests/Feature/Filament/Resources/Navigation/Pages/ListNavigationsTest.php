<?php

declare(strict_types=1);

use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Core\Models\Language;
use Capell\Navigation\Filament\Resources\Navigations\Pages\ListNavigations;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('navigation');

beforeEach(function (): void {
    Language::factory()->default()->create();

    test()->actingAsAdmin();
});

test('can list navigations', function (): void {
    $navigations = Navigation::factory()->count(5)->create();

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($navigations);
});

test('can search navigations', function (): void {
    $navigations = Navigation::factory()
        ->sequence(fn (Sequence $sequence): array => ['name' => sprintf('Language(%d)', $sequence->index)])
        ->count(3)
        ->create();

    $name = $navigations->random()->name;

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->searchTable($name)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords($navigations->where('name', $name))
        ->assertCanNotSeeTableRecords($navigations->where('name', '!=', $name));
});

test('can sort navigations', function (): void {
    $navigations = Navigation::factory()->count(5)->create();

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->sortTable('name')
        ->assertCanSeeTableRecords($navigations->sortBy('name'), inOrder: true);
});

test('can replicate navigation', function (): void {
    $navigation = Navigation::factory()->create();

    $name = $navigation->name . ' (copy)';
    $key = $navigation->key . '-copy';

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callAction(
            TestAction::make(ReplicateAction::class)->table($navigation),
            data: [
                'name' => $name,
                'key' => $key,
            ],
        )
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(2);

    assertDatabaseHas('navigations', [
        'name' => $name,
        'key' => $key,
    ]);
});

test('can delete navigation', function (): void {
    $navigation = Navigation::factory()->create();

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callAction(TestAction::make(DeleteAction::class)->table($navigation))
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);

    assertSoftDeleted($navigation, ['id' => $navigation->id]);
});

test('can group delete navigations', function (): void {
    $navigations = Navigation::factory()->count(5)->create();

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->selectTableRecords($navigations)
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->assertHasNoFormErrors();

    foreach ($navigations as $navigation) {
        assertSoftDeleted($navigation, ['id' => $navigation->id]);
    }
});
