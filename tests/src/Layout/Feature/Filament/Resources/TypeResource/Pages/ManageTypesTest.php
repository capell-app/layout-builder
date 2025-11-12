<?php

declare(strict_types=1);

use Capell\Admin\Enums\SchemaTypeEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\Types\Pages\ManageTypes;
use Capell\Core\Database\Factories\TypeFactory;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\TypeEnum;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('type');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can create type', function (TypeEnum $type): void {
    $record = Type::factory()->make();

    $hasTypeSchema = CapellAdmin::hasSchema(SchemaTypeEnum::Type, $type->name);

    $admin = $record->admin;

    if ($hasTypeSchema) {
        $admin['type_schema'] = $type->name;
    }

    livewire(ManageTypes::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0)
        ->callAction(
            CreateAction::class,
            data: [
                'type' => $type->value,
                'name' => $record->name,
                'key' => $record->key,
                'meta' => [
                    'component' => 'example-content',
                ],
                $admin,
            ],
        )
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    assertDatabaseHas('types', [
        'name' => $record->name,
        'key' => $record->key,
    ]);
})->with(TypeEnum::cases());

test('can update type', function (TypeEnum $typeEnum): void {
    $type = Type::factory()
        ->type($typeEnum)
        ->when(
            CapellAdmin::hasSchema(SchemaTypeEnum::Type, $typeEnum->name),
            fn (TypeFactory $factory): TypeFactory => $factory->adminTypeSchema($typeEnum->name),
        )
        ->create();

    $newType = Type::factory()->make();

    livewire(ManageTypes::class)
        ->assertSuccessful()
        ->callAction(
            TestAction::make(EditAction::class)->table($type),
            data: [
                'name' => $newType->name,
                'key' => $newType->key,
            ],
        )
        ->assertHasNoFormErrors();

    expect($type->refresh())
        ->name->toBe($newType->name)
        ->key->toBe($newType->key);
})->with(TypeEnum::cases());
