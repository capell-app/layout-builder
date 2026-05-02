<?php

declare(strict_types=1);

use Capell\Admin\Enums\ConfiguratorTypeEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Resources\Types\Pages\ManageTypes;
use Capell\Core\Database\Factories\TypeFactory;
use Capell\Core\Models\Type;
use Capell\Mosaic\Enums\TypeEnum;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
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

    $hasTypeConfigurator = CapellAdmin::hasConfigurator(ConfiguratorTypeEnum::Type, $type->name);

    $admin = $record->admin;

    if ($hasTypeConfigurator) {
        $admin['type_configurator'] = $type->name;
    }

    livewire(ManageTypes::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0)
        ->mountAction(CreateAction::class)
        ->fillForm([
            'name' => $record->name,
            'key' => $record->key,
            'type' => $type->value,
            'meta' => [
                'component' => 'example-content',
            ],
            $admin,
        ])
        ->fillForm([
            'admin.configurator' => 'Default',
        ])
        ->callMountedAction()
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
            CapellAdmin::hasConfigurator(ConfiguratorTypeEnum::Type, $typeEnum->name),
            fn (TypeFactory $factory): TypeFactory => $factory->adminTypeConfigurator($typeEnum->name),
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
