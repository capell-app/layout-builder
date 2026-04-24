<?php

declare(strict_types=1);

use Capell\Address\Filament\Resources\Countries\Pages\ManageCountries;
use Capell\Address\Models\Country;
use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('country');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can list countries', function (): void {
    $countries = Country::factory()->count(5)->create();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords($countries->count())
        ->assertCanSeeTableRecords($countries);
});

test('can search countries', function (): void {
    $countries = Country::factory()
        ->count(3)
        ->sequence(fn (Sequence $sequence): array => ['name' => sprintf('Country(%d)', $sequence->index)])
        ->create();

    $name = $countries->random()->name;

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->searchTable($name)
        ->assertCanSeeTableRecords($countries->where('name', $name))
        ->assertCanNotSeeTableRecords($countries->where('name', '!=', $name));
});

test('can sort countries', function (): void {
    $countries = Country::factory()->count(10)->create();
    $sorted = Country::query()->orderBy('name')->pluck('id');

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords($countries->count())
        ->sortTable('name')
        ->assertCanSeeTableRecords($sorted, inOrder: true);
});

test('can replicate country', function (): void {
    $country = Country::factory()->create();

    $copyName = $country->name . ' (copy)';
    $copyIso2 = strtoupper(fake()->unique()->lexify('??'));
    $copyIso3 = strtoupper(fake()->unique()->lexify('???'));

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callAction(
            TestAction::make(ReplicateAction::class)->table($country),
            data: [
                'name' => $copyName,
                'iso2' => $copyIso2,
                'iso3' => $copyIso3,
                'language_id' => $country->language_id,
                'meta' => $country->meta,
            ],
        )
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(2);

    assertDatabaseHas('countries', [
        'name' => $copyName,
        'iso2' => $copyIso2,
        'iso3' => $copyIso3,
        'language_id' => $country->language_id,
    ]);
});

test('can create country', function (): void {
    $country = Country::factory()->make();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0)
        ->callAction(
            CreateAction::class,
            [
                'name' => $country->name,
                'iso2' => $country->iso2,
                'iso3' => $country->iso3,
                'language_id' => $country->language_id,
                'meta' => $country->meta,
            ],
        )
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    assertDatabaseHas('countries', [
        'name' => $country->name,
        'iso2' => $country->iso2,
        'iso3' => $country->iso3,
        'language_id' => $country->language_id,
    ]);
});

test('can not create country', function (): void {
    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->callAction(
            CreateAction::class,
            data: [
                'name' => '',
                'iso2' => '',
                'iso3' => '',
            ],
        )
        ->assertHasFormErrors([
            'name' => ['required'],
            'iso2' => ['required'],
            'iso3' => ['required'],
        ])
        ->assertCountTableRecords(0);
});

test('can update country', function (): void {
    $country = Country::factory()->create();
    $newCountry = Country::factory()->make();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->callAction(
            TestAction::make(EditAction::class)->table($country),
            data: [
                'name' => $newCountry->name,
                'iso2' => $newCountry->iso2,
                'iso3' => $newCountry->iso3,
                'language_id' => $newCountry->language_id,
                'meta' => $newCountry->meta,
            ],
        )
        ->assertHasNoFormErrors();

    expect($country->refresh())
        ->name->toBe($newCountry->name)
        ->iso2->toBe($newCountry->iso2)
        ->iso3->toBe($newCountry->iso3)
        ->language_id->toBe($newCountry->language_id);
});

test('can not update country', function (): void {
    $country = Country::factory()->create();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->callAction(
            TestAction::make(EditAction::class)->table($country),
            data: [
                'name' => '',
                'iso2' => '',
                'iso3' => '',
            ],
        )
        ->assertHasFormErrors([
            'name' => ['required'],
            'iso2' => ['required'],
            'iso3' => ['required'],
        ]);
});

test('can delete country', function (): void {
    $country = Country::factory()->create();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callAction(TestAction::make(DeleteAction::class)->table($country))
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);

    assertSoftDeleted($country, ['id' => $country->id]);
});

test('can group delete countries', function (): void {
    $countries = Country::factory()
        ->sequence(fn (Sequence $sequence): array => ['name' => 'test-' . $sequence->index])
        ->count(5)->create();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->selectTableRecords($countries)
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->assertHasNoFormErrors();

    foreach ($countries as $country) {
        assertSoftDeleted($country, ['id' => $country->id]);
    }
});
