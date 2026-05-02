<?php

declare(strict_types=1);

use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Mosaic\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\Mosaic\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\Mosaic\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can create article widget type', function (): void {
    $newData = Widget::factory()->make();

    $typeCreator = new BlogCreator;

    $type = $typeCreator->createArticleWidgetType();

    livewire(ListWidgets::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);

    Widget::query()->create([
        'name' => $newData->name,
        'key' => str($newData->name)->slug()->toString(),
        'type_id' => $type->id,
        'status' => true,
    ]);

    assertDatabaseHas(Widget::class, [
        'name' => $newData->name,
        'key' => str($newData->name)->slug()->toString(),
        'type_id' => $type->id,
    ]);
});

test('can edit article widget', function (): void {
    $typeCreator = new BlogCreator;

    $type = $typeCreator->createArticleWidgetType();

    $newData = Widget::factory()->make();

    $widget = Widget::factory()->for($type)->create();

    livewire(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->assertSchemaStateSet([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('key')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($widget->refresh())
        ->name->toBe($newData->name)
        ->key->toBe($newData->key);
});
