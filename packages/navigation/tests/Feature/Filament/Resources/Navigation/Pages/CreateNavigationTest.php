<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Filament\Resources\Navigations\Pages\CreateNavigation;
use Capell\Navigation\Filament\Resources\Navigations\Pages\EditNavigation;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('navigation');

beforeEach(function (): void {
    Language::factory()->default()->create();
    Site::factory()->default()->create();
    Type::factory()->navigation()->create();

    test()->actingAsAdmin();
});

test('required fields are required', function (): void {
    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->call('create')
        ->assertHasAllFormErrors([
            'name' => 'required',
            'key' => 'required',
        ]);
});

it('can create', function (): void {
    $newData = Navigation::factory()->make();

    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirectToRoute(EditNavigation::getRouteName(), ['record' => Navigation::query()->latest()->first()->getRouteKey()]);

    assertDatabaseHas(Navigation::class, [
        'name' => $newData->name,
        'key' => $newData->key,
    ]);
});

it('can add items with link', function (): void {
    $newData = Navigation::factory()->make();
    $type = NavigationItemType::Link;
    $data = ['url' => 'https://example.com'];

    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->mountAction(
            TestAction::make('add')
                ->schemaComponent('items', schema: 'form'),
        )
        ->fillForm([
            'label' => 'Test',
            'type' => $type->value,
            'data' => $data,
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('create')
        ->assertHasNoFormErrors();

    $navigation = Navigation::query()->latest()->first();

    expect($navigation)
        ->name->toBe($newData->name)
        ->key->toBe($newData->key)
        ->items->toHaveCount(1)
        ->and($navigation->items->first())
        ->label->toBe('Test')
        ->type->toBe($type);
});

it('can add items with page', function (): void {
    $newData = Navigation::factory()->make();
    $type = NavigationItemType::Page;
    $data = [
        'pageable_type' => resolve(Page::class)->getMorphClass(),
        'pageable_id' => Page::factory()->withTranslations()->create()->id,
    ];

    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->mountAction(
            TestAction::make('add')
                ->schemaComponent('items', schema: 'form'),
        )
        ->fillForm([
            'label' => 'Test',
            'type' => $type->value,
            'data' => $data,
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('create')
        ->assertHasNoFormErrors();

    $navigation = Navigation::query()->latest()->first();

    expect($navigation)
        ->name->toBe($newData->name)
        ->key->toBe($newData->key)
        ->items->toHaveCount(1)
        ->and($navigation->items->first())
        ->label->toBe('Test')
        ->type->toBe($type);
});
