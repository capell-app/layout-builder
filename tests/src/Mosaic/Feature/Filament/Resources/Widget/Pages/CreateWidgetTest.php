<?php

declare(strict_types=1);

use Capell\Core\Models\Navigation;
use Capell\Mosaic\Database\Factories\WidgetTypeFactory;
use Capell\Mosaic\Enums\WidgetTypeEnum;
use Capell\Mosaic\Filament\Actions\CreateWidgetAction;
use Capell\Mosaic\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\Mosaic\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Support\Creator\TypeCreator;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

describe('from edit widget', function (): void {
    test('can create new widget', function (): void {
        $widget = Widget::factory()->create();
        $newData = Widget::factory()->make();

        livewire(EditWidget::class, ['record' => $widget->getRouteKey()])
            ->assertSuccessful()
            ->assertSchemaStateSet([
                'name' => $widget->name,
                'type_id' => $widget->type_id,
                'key' => $widget->key,
            ])
            ->mountAction(TestAction::make(CreateWidgetAction::class))
            ->assertFormFieldDoesNotExist('key')
            ->fillForm([
                'name' => $newData->name,
            ])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        assertDatabaseHas(Widget::class, [
            'name' => $newData->name,
            'key' => str($newData->name)->slug()->toString(),
            'type_id' => $widget->type_id,
        ]);
    });

    test('required fields are required', function (): void {
        $widget = Widget::factory()->create();

        livewire(EditWidget::class, ['record' => $widget->getRouteKey()])
            ->assertSuccessful()
            ->callAction(CreateWidgetAction::class, [
                'name' => '',
                'type_id' => '',
            ])
            ->assertHasFormErrors([
                'name' => 'required',
                'type_id' => 'required',
            ]);
    });
});

describe('from list widgets', function (): void {
    test('can create new widget', function (): void {
        $newData = Widget::factory()->make();

        livewire(ListWidgets::class)
            ->assertSuccessful()
            ->mountAction(TestAction::make(CreateWidgetAction::class))
            ->assertSchemaStateSet([
                'type_id' => $newData->type_id,
            ])
            ->assertFormFieldDoesNotExist('key')
            ->fillForm([
                'name' => $newData->name,
                'type_id' => $newData->type_id,
            ])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        assertDatabaseHas(Widget::class, [
            'name' => $newData->name,
            'key' => str($newData->name)->slug()->toString(),
            'type_id' => $newData->type_id,
        ]);
    });

    test('can create new widget type', function (WidgetTypeEnum $typeEum): void {
        $newData = Widget::factory()->make();

        $typeCreator = new TypeCreator;

        $type = match ($typeEum) {
            WidgetTypeEnum::Sections => $typeCreator->contentsWidgetType(),
            WidgetTypeEnum::Default => $typeCreator->defaultWidgetType(),
            WidgetTypeEnum::Media => $typeCreator->mediaWidgetType(),
            WidgetTypeEnum::Navigation => $typeCreator->navigationWidgetType(),
            WidgetTypeEnum::Pages => $typeCreator->pagesWidgetType(),
            WidgetTypeEnum::PageContents => $typeCreator->pageContentWidgetType(),
            WidgetTypeEnum::Results => $typeCreator->resultsWidgetType(),
            WidgetTypeEnum::Assets => $typeCreator->assetsWidgetType(),
            WidgetTypeEnum::System => $typeCreator->systemWidgetType(),
            WidgetTypeEnum::SectionBuilder => $typeCreator->contentBuilderWidgetType(),
            default => throw new Exception('Invalid widget type: ' . $typeEum->name),
        };

        livewire(ListWidgets::class)
            ->assertSuccessful()
            ->mountAction(CreateWidgetAction::class)
            ->fillForm([
                'name' => $newData->name,
                'key' => str($newData->name)->slug()->toString(),
                'type_id' => $type->id,
                ...match ($typeEum) {
                    WidgetTypeEnum::Navigation => ['meta' => ['navigation' => Navigation::factory()->create()->id]],
                    default => [],
                },
            ])
            ->callMountedAction()
            ->assertHasNoFormErrors()
            ->callMountedAction()
            ->assertHasNoFormErrors();

        assertDatabaseHas(Widget::class, [
            'name' => $newData->name,
            'key' => str($newData->name)->slug()->toString(),
            'type_id' => $type->id,
        ]);
    })
        ->with(WidgetTypeEnum::cases());

    test('required fields are required', function (): void {
        (new WidgetTypeFactory)->default()->create();

        livewire(ListWidgets::class)
            ->assertSuccessful()
            ->callAction(CreateWidgetAction::class, [
                'name' => '',
                'type_id' => '',
            ])
            ->assertHasFormErrors([
                'name' => 'required',
                'type_id' => 'required',
            ]);
    });
});
