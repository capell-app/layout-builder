<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Filament\Resources\Navigations\Pages\EditNavigation;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Str;

use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('navigation');

beforeEach(function (): void {
    Language::factory()->default()->create();

    test()->actingAsAdmin();
});

it('can retrieve data', function (): void {
    $navigation = Navigation::factory()->create();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'name' => $navigation->name,
            'key' => $navigation->key,
        ]);
});

it('can save', function (): void {
    $navigation = Navigation::factory()->create();
    $newData = Navigation::factory()->make();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($navigation->refresh())
        ->name->toBe($newData->name)
        ->key->toBe($newData->key);
});

test('validates edit navigation', function (): void {
    $navigation = Navigation::factory()->create();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => null,
            'key' => null,
        ])
        ->call('save')
        ->assertHasFormErrors([
            'name' => 'required',
            'key' => 'required',
        ]);
});

it('can delete', function (): void {
    $navigation = Navigation::factory()->create();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->callAction(DeleteAction::class)
        ->assertHasNoFormErrors();

    assertSoftDeleted($navigation, ['id' => $navigation->id]);
});

it('can edit items with link', function (): void {
    $uuid = (string) Str::uuid();
    $linkType = NavigationItemType::Link;
    $data = ['url' => 'https://example.com'];

    $navigation = Navigation::factory()
        ->items([
            $uuid => [
                'type' => $linkType,
                'label' => 'Initial Label',
                'data' => $data,
            ],
        ])
        ->create();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->mountAction(
            TestAction::make('edit')
                ->schemaComponent('items', schema: 'form')
                ->arguments(['cachedRecordKey' => $uuid, 'statePath' => 'data.items.' . $uuid]),
        )
        ->fillForm([
            'label' => 'Test',
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('save')
        ->assertHasNoFormErrors();

    expect($navigation->refresh())->items->toHaveCount(1)
        ->and($navigation->items[$uuid])
        ->label->toBe('Test')
        ->type->toBe($linkType);
});

it('can edit items with page', function (): void {
    $uuid = (string) Str::uuid();
    $linkType = NavigationItemType::Page;
    $page = Page::factory()->withTranslations()->create();
    $data = ['pageable_id' => $page->getKey(), 'pageable_type' => $page->getMorphClass()];

    $navigation = Navigation::factory()
        ->items([
            $uuid => [
                'type' => $linkType,
                'label' => 'Initial Label',
                'data' => $data,
            ],
        ])
        ->create();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->mountAction(
            TestAction::make('edit')
                ->schemaComponent('items', schema: 'form')
                ->arguments(['cachedRecordKey' => $uuid, 'statePath' => 'data.items.' . $uuid]),
        )
        ->fillForm([
            'label' => 'Test',
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('save')
        ->assertHasNoFormErrors();

    expect($navigation->refresh())->items->toHaveCount(1)
        ->and($navigation->items[$uuid])
        ->label->toBe('Test')
        ->type->toBe($linkType);
});
