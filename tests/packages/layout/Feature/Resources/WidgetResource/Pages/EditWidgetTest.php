<?php

declare(strict_types=1);

use Capell\Admin\Filament\Actions\ReplicateAction;
use Capell\Core\Models\Navigation;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Filament\Resources\WidgetResource\Pages\EditWidget;
use Capell\Layout\Models\Widget;
use Capell\Layout\Services\Creator\WidgetTypeCreator;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\DeleteAction;

use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('can retrieve data', function (): void {
    $widget = Widget::factory()->create();

    livewire(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertFormSet([
            'name' => $widget->name,
            'key' => $widget->key,
        ]);
});

it('can save', function (): void {
    $widget = Widget::factory()->create();
    $newData = Widget::factory()->make();

    livewire(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($widget->refresh())
        ->name->toBe($newData->name)
        ->key->toBe($newData->key);
});

test('validates edit widget', function (): void {
    $widget = Widget::factory()->create();

    livewire(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => '',
            'key' => '',
        ])
        ->call('save')
        ->assertHasAllFormErrors([
            'name' => 'required',
            'key' => 'required',
        ]);
});

test('can replicate widget', function (): void {
    $widget = Widget::factory()->create();
    $newData = Widget::factory()->make();

    livewire(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->assertSuccessful()
        ->callAction(ReplicateAction::class, [
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->assertHasNoFormErrors();

    expect(Widget::count())->toBe(2);
});

it('can delete', function (): void {
    $widget = Widget::factory()->create();

    livewire(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->assertSuccessful()
        ->callAction(DeleteAction::class)
        ->assertHasNoFormErrors();

    assertSoftDeleted($widget, ['id' => $widget->id]);
});

test('can edit widget', function (WidgetTypeEnum $typeEum): void {
    $typeCreator = new WidgetTypeCreator;

    $type = match ($typeEum) {
        WidgetTypeEnum::Contents => $typeCreator->contentsWidgetType(),
        WidgetTypeEnum::Default => $typeCreator->defaultWidgetType(),
        WidgetTypeEnum::Media => $typeCreator->mediaWidgetType(),
        WidgetTypeEnum::Navigation => $typeCreator->navigationWidgetType(),
        WidgetTypeEnum::Pages => $typeCreator->pagesWidgetType(),
        WidgetTypeEnum::PageContents => $typeCreator->pageContentWidgetType(),
        WidgetTypeEnum::PageResults => $typeCreator->pageResultsWidgetType(),
        WidgetTypeEnum::Assets => $typeCreator->assetsWidgetType(),
        WidgetTypeEnum::System => $typeCreator->systemWidgetType(),
    };

    $newData = Widget::factory()->make();

    $widget = Widget::factory()->for($type)->create();

    livewire(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'key' => $newData->key,
            ...match ($typeEum) {
                WidgetTypeEnum::Navigation => ['meta' => ['navigation' => Navigation::factory()->create()->handle]],
                default => [],
            },
        ])
        ->assertFormSet([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('key')
        ->call('save')
        ->assertHasNoActionErrors();

    expect($widget->refresh())
        ->name->toBe($newData->name)
        ->key->toBe($newData->key);
})->with(WidgetTypeEnum::cases());
