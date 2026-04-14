<?php

declare(strict_types=1);

use Capell\Admin\Filament\Actions\ReplicateAction;
use Capell\Core\Models\Navigation;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Enums\ActionLinkEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\Layout\Models\Widget;
use Capell\Layout\Support\Creator\TypeCreator;
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
        ->assertSchemaStateSet([
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

    expect(Widget::query()->count())->toBe(2);
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
    $typeCreator = new TypeCreator;

    $type = match ($typeEum) {
        WidgetTypeEnum::Contents => $typeCreator->contentsWidgetType(),
        WidgetTypeEnum::Default => $typeCreator->defaultWidgetType(),
        WidgetTypeEnum::Media => $typeCreator->mediaWidgetType(),
        WidgetTypeEnum::Navigation => $typeCreator->navigationWidgetType(),
        WidgetTypeEnum::Pages => $typeCreator->pagesWidgetType(),
        WidgetTypeEnum::PageContents => $typeCreator->pageContentWidgetType(),
        WidgetTypeEnum::Results => $typeCreator->resultsWidgetType(),
        WidgetTypeEnum::Assets => $typeCreator->assetsWidgetType(),
        WidgetTypeEnum::System => $typeCreator->systemWidgetType(),
        WidgetTypeEnum::ContentBuilder => $typeCreator->contentBuilderWidgetType(),
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
                WidgetTypeEnum::Navigation => ['meta' => ['navigation' => Navigation::factory()->create()->id]],
                default => [],
            },
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
})->with(WidgetTypeEnum::cases());

it('can save a widget with actions', function (): void {
    $site = Site::factory()->create();

    $actions = [
        [
            'type' => ActionLinkEnum::Link->value,
            'url' => 'https://example.com',
        ],
        [
            'type' => ActionLinkEnum::Page->value,
            'pageable_type' => resolve(Page::class)->getMorphClass(),
            'pageable_id' => Page::factory()->site($site)->create()->id,
            'site_id' => $site->id,
        ],
    ];

    $widget = Widget::factory()->meta('actions', $actions)->create();

    livewire(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->assertSuccessful()
        ->call('save')
        ->assertHasNoFormErrors();
});
