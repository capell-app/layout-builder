<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Database\Factories\WidgetTypeFactory;
use Capell\LayoutBuilder\Enums\WidgetTypeEnum;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\Navigation\Models\Navigation;
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
            ->mountAction(TestAction::make('create'))
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
            ->callAction('create', [
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
            ->assertCountTableRecords(0);

        Widget::query()->create([
            'name' => $newData->name,
            'key' => str($newData->name)->slug()->toString(),
            'type_id' => $newData->type_id,
            'status' => true,
        ]);

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
            WidgetTypeEnum::Hero => $typeCreator->heroWidgetType(),
            WidgetTypeEnum::HeroBanner => $typeCreator->heroBannerWidgetType(),
            WidgetTypeEnum::CardGrid => $typeCreator->cardGridWidgetType(),
            WidgetTypeEnum::FeatureList => $typeCreator->featureListWidgetType(),
            WidgetTypeEnum::CTASection => $typeCreator->ctaSectionWidgetType(),
            WidgetTypeEnum::ImageGallery => $typeCreator->imageGalleryWidgetType(),
        };

        livewire(ListWidgets::class)
            ->assertSuccessful()
            ->assertCountTableRecords(0);

        Widget::query()->create([
            'name' => $newData->name,
            'key' => str($newData->name)->slug()->toString(),
            'type_id' => $type->id,
            'status' => true,
            ...match ($typeEum) {
                WidgetTypeEnum::Navigation => ['meta' => ['navigation' => Navigation::factory()->create()->id]],
                default => [],
            },
        ]);

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
            ->callAction('create', [
                'name' => '',
                'type_id' => '',
            ])
            ->assertHasFormErrors([
                'name' => 'required',
                'type_id' => 'required',
            ]);
    });
});
