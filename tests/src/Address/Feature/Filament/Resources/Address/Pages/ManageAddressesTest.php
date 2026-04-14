<?php

declare(strict_types=1);

use Capell\Address\Filament\Resources\Addresses\Pages\ManageAddresses;
use Capell\Address\Models\Address;
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
    ->group('address');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can list addresses', function (): void {
    $addresses = Address::factory()->count(5)->create();

    livewire(ManageAddresses::class)
        ->assertSuccessful()
        ->assertCountTableRecords($addresses->count())
        ->assertCanSeeTableRecords($addresses);
});

test('can search addresses', function (): void {
    $addresses = Address::factory()
        ->count(3)
        ->sequence(fn (Sequence $sequence): array => ['name' => sprintf('Address(%d)', $sequence->index)])
        ->create();

    $name = $addresses->random()->name;

    livewire(ManageAddresses::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->searchTable($name)
        ->assertCanSeeTableRecords($addresses->where('name', $name))
        ->assertCanNotSeeTableRecords($addresses->where('name', '!=', $name));
});

test('can sort addresses', function (): void {
    $addresses = Address::factory()->count(10)->create();
    $sorted = Address::query()->orderBy('name')->pluck('id');

    livewire(ManageAddresses::class)
        ->assertSuccessful()
        ->assertCountTableRecords($addresses->count())
        ->sortTable('name')
        ->assertCanSeeTableRecords($sorted, inOrder: true);
});

test('can replicate address', function (): void {
    $address = Address::factory()->create();

    $copyName = $address->name . ' (copy)';
    $copyLine1 = $address->line1 . ' (copy)';

    livewire(ManageAddresses::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->mountAction(TestAction::make(ReplicateAction::class)->table($address))
        ->assertActionMounted(TestAction::make(ReplicateAction::class)->table($address))
        ->fillForm([
            'name' => $copyName,
            'line1' => $copyLine1,
            'line2' => $address->line2,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country_id' => $address->country_id,
            'meta' => $address->meta,
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(2);

    assertDatabaseHas('addresses', [
        'name' => $copyName,
        'line1' => $copyLine1,
        'city' => $address->city,
        'state' => $address->state,
        'postal_code' => $address->postal_code,
        'country_id' => $address->country_id,
    ]);
});

test('can create address', function (): void {
    $address = Address::factory()->make();

    livewire(ManageAddresses::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0)
        ->callAction(
            CreateAction::class,
            [
                'name' => $address->name,
                'line1' => $address->line1,
                'line2' => $address->line2,
                'city' => $address->city,
                'state' => $address->state,
                'postal_code' => $address->postal_code,
                'country_id' => $address->country_id,
                'meta' => $address->meta,
            ],
        )
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    assertDatabaseHas('addresses', [
        'name' => $address->name,
        'line1' => $address->line1,
        'city' => $address->city,
        'state' => $address->state,
        'postal_code' => $address->postal_code,
        'country_id' => $address->country_id,
    ]);
});

test('can not create address', function (): void {
    livewire(ManageAddresses::class)
        ->assertSuccessful()
        ->callAction(
            CreateAction::class,
            data: [
                'name' => '',
                'line1' => '',
                'city' => '',
                'state' => '',
                'postal_code' => '',
            ],
        )
        ->assertHasFormErrors([
            'name' => ['required'],
            'line1' => ['required'],
            'city' => ['required'],
            'state' => ['required'],
            'postal_code' => ['required'],
        ])
        ->assertCountTableRecords(0);
});

test('can update address', function (): void {
    $address = Address::factory()->create();
    $newAddress = Address::factory()->make();

    livewire(ManageAddresses::class)
        ->assertSuccessful()
        ->callAction(
            TestAction::make(EditAction::class)->table($address),
            data: [
                'name' => $newAddress->name,
                'line1' => $newAddress->line1,
                'line2' => $newAddress->line2,
                'city' => $newAddress->city,
                'state' => $newAddress->state,
                'postal_code' => $newAddress->postal_code,
                'country_id' => $newAddress->country_id,
                'meta' => $newAddress->meta,
            ],
        )
        ->assertHasNoFormErrors();

    expect($address->refresh())
        ->name->toBe($newAddress->name)
        ->line1->toBe($newAddress->line1)
        ->city->toBe($newAddress->city)
        ->state->toBe($newAddress->state)
        ->postal_code->toBe($newAddress->postal_code)
        ->country_id->toBe($newAddress->country_id);
});

test('can not update address', function (): void {
    $address = Address::factory()->create();

    livewire(ManageAddresses::class)
        ->assertSuccessful()
        ->callAction(
            TestAction::make(EditAction::class)->table($address),
            data: [
                'name' => '',
                'line1' => '',
                'city' => '',
                'state' => '',
                'postal_code' => '',
            ],
        )
        ->assertHasFormErrors([
            'name' => ['required'],
            'line1' => ['required'],
            'city' => ['required'],
            'state' => ['required'],
            'postal_code' => ['required'],
        ]);
});

test('can delete address', function (): void {
    $address = Address::factory()->create();

    livewire(ManageAddresses::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callAction(TestAction::make(DeleteAction::class)->table($address))
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);

    assertSoftDeleted($address, ['id' => $address->id]);
});

test('can group delete addresses', function (): void {
    $addresses = Address::factory()
        ->sequence(fn (Sequence $sequence): array => ['name' => 'test-' . $sequence->index])
        ->count(5)->create();

    livewire(ManageAddresses::class)
        ->assertSuccessful()
        ->selectTableRecords($addresses)
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->assertHasNoFormErrors();

    foreach ($addresses as $address) {
        assertSoftDeleted($address, ['id' => $address->id]);
    }
});
