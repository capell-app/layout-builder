<?php

declare(strict_types=1);

use Capell\Core\Models\Navigation;
use Capell\Core\Models\Type;
use Capell\Layout\Database\Factories\WidgetTypeFactory;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Filament\Actions\CreateWidgetModalAction;
use Capell\Layout\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\Layout\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\Layout\Models\Widget;
use Capell\Layout\Services\Creator\TypeCreator;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

describe('from edit page', function (): void {
    test('can create new widget', function (): void {
        Type::factory()->type(LayoutTypeEnum::Widget)->create();

        $widget = Widget::factory()->create();

        $newData = Widget::factory()->make();

        livewire(EditWidget::class, ['record' => $widget->getRouteKey()])
            ->assertSuccessful()
            ->callAction(CreateWidgetModalAction::class, [
                'name' => $newData->name,
                'key' => $newData->key,
            ])
            ->assertHasNoFormErrors();

        assertDatabaseHas(Widget::class, [
            'name' => $newData->name,
            'key' => $newData->key,
        ]);
    });

    test('required fields are required', function (): void {
        $widget = Widget::factory()->create();

        livewire(EditWidget::class, ['record' => $widget->getRouteKey()])
            ->assertSuccessful()
            ->callAction(CreateWidgetModalAction::class, [
                'name' => '',
                'key' => '',
            ])
            ->assertHasFormErrors([
                'name' => 'required',
                'key' => 'required',
            ]);
    });
});

describe('from list page', function (): void {
    test('can create new widget', function (): void {
        Type::factory()->type(LayoutTypeEnum::Widget)->create();
        $newData = Widget::factory()->make();

        livewire(ListWidgets::class)
            ->assertSuccessful()
            ->callAction(CreateWidgetModalAction::class, [
                'name' => $newData->name,
                'key' => $newData->key,
            ])
            ->assertHasNoFormErrors();

        assertDatabaseHas(Widget::class, [
            'name' => $newData->name,
            'key' => $newData->key,
        ]);
    });

    test('can create new widget type', function (WidgetTypeEnum $typeEum): void {
        $newData = Widget::factory()->make();

        $typeCreator = new TypeCreator;

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
            default => throw new Exception('Invalid widget type: ' . $typeEum->name),
        };

        livewire(ListWidgets::class)
            ->assertSuccessful()
            ->callAction(CreateWidgetModalAction::class, [
                'name' => $newData->name,
                'key' => $newData->key,
                'type_id' => $type->id,
                ...match ($typeEum) {
                    WidgetTypeEnum::Navigation => ['meta' => ['navigation' => Navigation::factory()->create()->id]],
                    default => [],
                },
            ])
            ->assertHasNoFormErrors();

        assertDatabaseHas(Widget::class, [
            'name' => $newData->name,
            'key' => $newData->key,
            'type_id' => $type->id,
        ]);
    })
        ->with(WidgetTypeEnum::cases());

    test('required fields are required', function (): void {
        (new WidgetTypeFactory)->default()->create();

        livewire(ListWidgets::class)
            ->assertSuccessful()
            ->callAction(CreateWidgetModalAction::class, [
                'name' => '',
                'key' => '',
            ])
            ->assertHasFormErrors([
                'name' => 'required',
                'key' => 'required',
            ]);
    });
});
