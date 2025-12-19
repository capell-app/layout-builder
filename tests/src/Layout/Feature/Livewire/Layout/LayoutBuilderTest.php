<?php

declare(strict_types=1);

use Capell\Admin\Enums\LayoutEnum;
use Capell\Admin\Services\Creator\LayoutCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Database\Factories\WidgetTypeFactory;
use Capell\Layout\Enums\AssetEnum;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Widgets\DefaultLayoutWidgetSchema;
use Capell\Layout\Livewire\Layout\WidgetTableSelect;
use Capell\Layout\Livewire\LayoutBuilder;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Capell\Layout\Services\Creator\LayoutCreator;
use Capell\Layout\Services\Creator\TypeCreator;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;
use Pest\Expectation;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('Render layout builder', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->assertSeeText(__('capell-layout::heading.layout_record', ['name' => $layout->name]));
});

test('can edit layouts', function (LayoutEnum $layoutEnum): void {
    $language = Language::factory()->create();

    $layout = resolve(LayoutCreator::class)->create($layoutEnum->value);

    $widgetTypeCreator = resolve(TypeCreator::class);
    $widgetTypeCreator->createWidgetTypes();

    $widgetCreator = resolve(WidgetCreator::class);
    $widgetCreator->createWidgets(collect([$language]));

    $layoutUpdater = resolve(LayoutCreator::class);
    $layoutUpdater->setup($layout->key);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->assertSeeText(__('capell-layout::heading.layout_record', ['name' => $layout->name]));
})->with(LayoutEnum::cases());

test('Render layout builder with page', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $page->id,
    ])
        ->assertSuccessful()
        ->assertSeeText(__('capell-layout::heading.layout_record', ['name' => $layout->name]));
});

test('Render layout without containers', function (): void {
    $layout = (new LayoutFactory)->state(['containers' => []])->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->assertSeeHtml(__('capell-layout::message.layout_empty'));
});

test('Render layout with widget and assets', function (AssetEnum|Capell\Core\Enums\AssetEnum $assetType): void {
    $widget = Widget::factory()
        ->for((new WidgetTypeFactory)->state([
            'admin' => [
                'asset_types' => [$assetType],
            ],
        ]))
        ->has(WidgetAsset::factory()->asset($assetType), 'assets')
        ->create();
    $layout = (new LayoutFactory)->state(['containers' => ['main' => ['widgets' => [['widget_key' => $widget->key]]]]])
        ->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful();
})->with([AssetEnum::Content, ...Capell\Core\Enums\AssetEnum::cases()]);

test('Can reorder containers', function (): void {
    $widget = Widget::factory()->create(['key' => 'test']);
    $layout = (new LayoutFactory)->state([
        'containers' => [
            'first' => ['widgets' => [['widget_key' => $widget->key]]],
            'second' => ['widgets' => [['widget_key' => $widget->key]]],
        ],
    ])->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->call(
            'reorderContainers',
            containerKey: 'second',
            position: 0,
        )
        ->call('saveLayout');

    $layout->refresh();

    expect(array_keys($layout->containers))
        ->toBe(['second', 'first']);
});

test('Can reorder widgets', function (): void {
    Widget::factory()->state(['key' => 'first'])->create();
    Widget::factory()->state(['key' => 'second'])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => [
            'test' => [
                'widgets' => [
                    ['widget_key' => 'first', 'occurrence' => 1],
                    ['widget_key' => 'second', 'occurrence' => 1],
                ],
            ],
        ],
    ])->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->call(
            'reorderWidgets',
            containerKey: 'test',
            containerWidgetIndex: 'test.1',
            widgetIndex: 0,
        )
        ->call('saveLayout');

    $layout->refresh();

    expect(array_column($layout->containers['test']['widgets'], 'widget_key'))
        ->toBe(['second', 'first']);
});

todo('Can reorder widgets into different container');

test('Can add container', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    $containerKey = array_key_first($layout->containers) . '-2';
    $htmlClass = 'test-class';

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->callAction(
            'addContainer',
            arguments: [
                'containerKey' => $containerKey,
            ],
            data: [
                'key' => $containerKey,
                'meta' => [
                    'html_class' => $htmlClass,
                ],
            ],
        )
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers)
        ->toHaveKey($containerKey)
        ->and($layout->containers[$containerKey]['meta']['html_class'])
        ->toEqual($htmlClass);
});

test('Can clone layout', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $page->id,
    ])
        ->assertSuccessful()
        ->callAction('duplicateLayoutAction')
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    $clonedLayout = Layout::query()->where('name', $layout->name . ' 1')
        ->where('key', $layout->key . '-1')
        ->first();

    expect($clonedLayout)
        ->toBeInstanceOf(Layout::class)
        ->not->toBe($layout)
        ->containers->toEqual($layout->containers);
});

test('removeContainer action', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    $containerKey = array_key_first($layout->containers);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->callAction('removeContainer', arguments: [
            'containerKey' => $containerKey,
        ])
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers)
        ->not->toHaveKey($containerKey);
});

test('Can save layout without editing container', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    $containers = $layout->containers;

    $containerKey = array_key_first($containers);

    $containerWidget = $containers[$containerKey]['widgets'][0];

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->count(2)
        ->widget($widget)
        ->occurrence($containerWidget['occurrence'])
        ->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->set('layoutModified', true)
        ->call('saveLayout');

    expect($layout->refresh())
        ->containers
        ->toHaveCount(count($containers))
        ->toEqual($containers);

    $assets = WidgetAsset::query()
        ->where('widget_id', $widget->id)
        ->whereNull('page_id')
        ->exists();

    expect($assets)
        ->toBeTrue();
});

test('Can edit container', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    $containerKey = array_key_first($layout->containers);
    $newContainerKey = $containerKey . '-new';

    $containerWidget = $layout->containers[$containerKey]['widgets'][0];

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->count(2)
        ->widget($widget)
        ->occurrence($containerWidget['occurrence'])
        ->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->callAction(
            'editContainer',
            data: [
                'key' => $newContainerKey,
            ],
            arguments: [
                'containerKey' => $containerKey,
            ],
        )
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    expect($layout->refresh())
        ->containers
        ->toHaveKey($newContainerKey);

    $assets = WidgetAsset::query()
        ->where('widget_id', $widget->id)
        ->whereNull('page_id')
        ->get();

    expect($assets)
        ->toHaveCount(2)
        ->each(
            fn (Expectation $expect) => $expect
                ->widget_id->toEqual($widget->id)
                ->container->toBeNull()
                ->page_id->toBeNull(),
        );
});

test('Can edit container for page layout', function (string $widgetKey): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    $containerKey = array_key_first($layout->containers);
    $newContainerKey = $containerKey . '-new';

    foreach ($layout->containers as $container) {
        foreach ($container['widgets'] as $widget) {
            if ($widget['widget_key'] === $widgetKey) {
                $containerWidget = $widget;
                break 2;
            }
        }
    }

    throw_unless(isset($containerWidget), Exception::class, sprintf('Widget with key %s not found in layout containers.', $widgetKey));

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->count(2)
        ->widget($widget)
        ->page($page, $containerKey, $containerWidget['occurrence'])
        ->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $page->id,
    ])
        ->assertSuccessful()
        ->callAction(
            'editContainer',
            data: [
                'key' => $newContainerKey,
            ],
            arguments: [
                'containerKey' => $containerKey,
            ],
        )
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    expect($layout->refresh())
        ->containers
        ->toHaveKey($newContainerKey);

    $assets = WidgetAsset::query()
        ->where('widget_id', $widget->id)
        ->where('page_id', $page->id)
        ->get();

    expect($assets)
        ->toHaveCount(2)
        ->each(
            fn (Expectation $expect) => $expect
                ->container->toEqual($newContainerKey)
                ->widget_id->toEqual($widget->id)
                ->page_id->toEqual($page->id),
        );
})->with(['first', 'second']);

test('Can add new widget', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $widget = Widget::factory()->state(['key' => 'test'])->create();

    $containerKey = array_key_first($layout->containers);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->mountAction(
            'addWidget',
            arguments: [
                'containerKey' => $containerKey,
            ],
        )
        ->assertSeeLivewire(WidgetTableSelect::class)
        ->callMountedAction()
        ->dispatch(
            'add-widgets-to-container',
            containerKey: $containerKey,
            widgets: [$widget->id],
        )
        ->call('saveLayout');

    $layout->refresh();

    $lastWidgetKey = array_key_last($layout->containers[$containerKey]['widgets']);

    expect($layout->containers[$containerKey]['widgets'][$lastWidgetKey])
        ->toBeArray()
        ->toEqual([
            'widget_key' => $widget->key,
            'occurrence' => 1,
        ]);
});

test('Can add existing widget', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    $containerKey = array_key_first($layout->containers);
    $lastWidgetKey = array_key_last($layout->containers[$containerKey]['widgets']);
    $lastWidget = $layout->containers[$containerKey]['widgets'][$lastWidgetKey];

    $widget = Widget::query()->firstWhere('key', $lastWidget['widget_key']);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->mountAction(
            'addWidget',
            arguments: [
                'containerKey' => $containerKey,
            ],
        )
        ->assertSeeLivewire(WidgetTableSelect::class)
        ->callMountedAction()
        ->dispatch(
            'add-widgets-to-container',
            containerKey: $containerKey,
            widgets: [$widget->id],
        )
        ->call('saveLayout');

    $layout->refresh();

    $lastWidgetKey = array_key_last($layout->containers[$containerKey]['widgets']);

    expect($layout->containers[$containerKey]['widgets'][$lastWidgetKey])
        ->toBeArray()
        ->toEqual([
            'widget_key' => $widget->key,
            'occurrence' => $lastWidget['occurrence'] + 1,
        ]);
});

test('Can edit container widget', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_last($layout->containers[$containerKey]['widgets']);

    $widget = Widget::factory()
        ->for((new WidgetTypeFactory)->state([
            'admin' => [
                'layout_container_widget_schema' => DefaultLayoutWidgetSchema::getKey(),
            ],
        ]), 'type')
        ->create();

    $widgetIndex++;

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->mountAction(
            'addWidget',
            arguments: [
                'containerKey' => $containerKey,
            ],
        )
        ->assertSeeLivewire(WidgetTableSelect::class)
        ->dispatch(
            'add-widgets-to-container',
            containerKey: $containerKey,
            widgets: [$widget->id],
        )
        ->callMountedAction()
        ->mountAction(
            TestAction::make('editContainerWidget')
                ->arguments([
                    'containerKey' => $containerKey,
                    'widgetIndex' => $widgetIndex,
                ]),
        )
        ->fillForm(['html_class' => 'foo'])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers[$containerKey]['widgets'][$widgetIndex])
        ->toBeArray()
        ->widget_key->toEqual($widget->key)
        ->occurrence->toEqual(1)
        ->meta->toMatchArray(['html_class' => 'foo']);
});

test('Can duplicate widget', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_last($layout->containers[$containerKey]['widgets']);
    $lastWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::query()->firstWhere('key', $lastWidget['widget_key']);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->callAction(
            'duplicateWidget',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
            ],
        )
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    $layout->refresh();

    $lastWidgetKey = array_key_last($layout->containers[$containerKey]['widgets']);

    expect($layout->containers[$containerKey]['widgets'][$lastWidgetKey])
        ->toBeArray()
        ->toEqual([
            'widget_key' => $widget->key,
            'occurrence' => 3,
        ]);
});

test('Can remove widget', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_last($layout->containers[$containerKey]['widgets']);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->callAction('removeWidget', arguments: [
            'containerKey' => $containerKey,
            'widgetIndex' => $widgetIndex,
        ]);
});
