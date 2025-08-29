<?php

declare(strict_types=1);

use Capell\Admin\Enums\LayoutEnum as AdminLayoutEnum;
use Capell\Admin\Services\Creator\LayoutCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Database\Factories\WidgetTypeFactory;
use Capell\Layout\Enums\AssetEnum;
use Capell\Layout\Enums\LayoutEnum;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Widgets\DefaultLayoutWidgetSchema;
use Capell\Layout\Livewire\LayoutBuilder;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Capell\Layout\Services\Creator\LayoutUpdater;
use Capell\Layout\Services\Creator\TypeCreator;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;

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
        ->assertSeeText($layout->name . ' Layout');
});

test('can edit layouts', function (AdminLayoutEnum|LayoutEnum $layoutEnum): void {
    $language = Language::factory()->create();

    if ($layoutEnum instanceof LayoutEnum) {
        $layout = app(Capell\Layout\Services\Creator\LayoutCreator::class)->create($layoutEnum->value);
    } else {
        $layout = app(LayoutCreator::class)->create($layoutEnum->value);
    }

    $widgetTypeCreator = app(TypeCreator::class);
    $widgetTypeCreator->createWidgetTypes();

    $widgetCreator = app(WidgetCreator::class);
    $widgetCreator->createWidgets(collect([$language]));

    $layoutUpdater = app(LayoutUpdater::class);
    $layoutUpdater->setup($layout->key);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->assertSeeText($layout->name . ' Layout');
})->with([...AdminLayoutEnum::cases(), ...LayoutEnum::cases()]);

test('Render layout builder with page', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $page->id,
        'site_id' => $page->site_id,
    ])
        ->assertSuccessful()
        ->assertSeeText($layout->name . ' Layout');
});

test('Render layout without containers', function (): void {
    $layout = (new LayoutFactory)->state(['containers' => []])->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->assertSeeHtml('Layout is empty. Add a container and widget(s) to get started');
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

test('Save layout builder', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->set('containers', [])
        ->call('saveLayout');

    expect($layout->refresh())
        ->containers->toBeEmpty();
});

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
            position: 0
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
            widgetIndex: 0
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
            ]
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

    $clonedLayout = Layout::where('name', $layout->name . ' 1')
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

test('Can edit container', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    $containerKey = array_key_first($layout->containers);
    $newContainerKey = $containerKey . '-new';

    $containerWidget = $layout->containers[$containerKey]['widgets'][0];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

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
            ]
        )
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    expect($layout->refresh())
        ->containers
        ->toHaveKey($newContainerKey);

    $assets = WidgetAsset::query()
        ->where('widget_id', $widget->id)
        ->whereNull('page_id')
        ->exists();

    expect($assets)
        ->toBeTrue();
});

test('Can add new widget', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $widget = Widget::factory()->state(['key' => 'test'])->create();

    $containerKey = array_key_first($layout->containers);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->callAction(
            'addWidget',
            data: [
                'container' => $containerKey,
                'type_id' => [$widget->type_id],
                'widgets' => [$widget->id],
            ],
            arguments: [
                'containerKey' => $containerKey,
            ]
        )
        ->assertHasNoFormErrors()
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

    $widget = Widget::firstWhere('key', $lastWidget['widget_key']);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->callAction(
            'addWidget',
            data: [
                'type_id' => [$widget->type_id],
                'widgets' => [$widget->id],
            ],
            arguments: [
                'containerKey' => $containerKey,
            ]
        )
        ->assertHasNoFormErrors()
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
        ->callAction(
            'addWidget',
            data: ['widgets' => [$widget->id]],
            arguments: ['containerKey' => $containerKey]
        )
        ->assertHasNoFormErrors()
        ->mountAction(
            TestAction::make('editContainerWidget')
                ->arguments([
                    'containerKey' => $containerKey,
                    'widgetIndex' => $widgetIndex,
                ])
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

    $widget = Widget::firstWhere('key', $lastWidget['widget_key']);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->callAction(
            'duplicateWidget',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
            ]
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
