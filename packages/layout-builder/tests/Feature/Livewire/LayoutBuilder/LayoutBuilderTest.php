<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\LayoutCreator;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Database\Factories\WidgetTypeFactory;
use Capell\LayoutBuilder\Enums\AssetEnum;
use Capell\LayoutBuilder\Enums\ContainerAlignmentEnum;
use Capell\LayoutBuilder\Enums\ResponsiveVisibilityEnum;
use Capell\LayoutBuilder\Filament\Configurators\Layouts\Widgets\DefaultLayoutWidgetConfigurator;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Facades\Gate;
use Pest\Expectation;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

function invokeLayoutBuilderMethod(object $component, string $methodName, array $arguments = []): null
{
    $reflectionMethod = new ReflectionMethod($component->instance(), $methodName);
    $reflectionMethod->invokeArgs($component->instance(), $arguments);

    return null;
}

// ──────────────────────────────────────────────
// Rendering
// ──────────────────────────────────────────────

test('it renders the layout builder with containers', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->assertSeeText(__('capell-layout-builder::heading.layout_record', ['name' => $layout->name]));
});

test('it renders the layout builder for a page', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    livewire(LayoutBuilder::class, [
        'layout' => $layout,
        'page' => $page,
    ])
        ->assertSuccessful()
        ->assertSeeText(__('capell-layout-builder::heading.layout_record', ['name' => $layout->name]));
});

test('it renders an empty layout message when no containers exist', function (): void {
    $layout = (new LayoutFactory)->state(['containers' => []])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->assertSeeHtml(__('capell-layout-builder::message.layout_empty'));
});

test('it renders for each layout enum type', function (LayoutEnum $layoutEnum): void {
    $language = Language::factory()->create();

    $layout = resolve(LayoutCreator::class)->create($layoutEnum->value);

    resolve(TypeCreator::class)->createWidgetTypes();
    resolve(WidgetCreator::class)->createWidgets(collect([$language]));

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->assertSeeText(__('capell-layout-builder::heading.layout_record', ['name' => $layout->name]));
})->with(LayoutEnum::cases());

test('it renders widgets with asset types', function (AssetEnum|Capell\Core\Enums\AssetEnum $assetType): void {
    $widget = Widget::factory()
        ->for((new WidgetTypeFactory)->state([
            'admin' => [
                'asset_types' => [$assetType],
            ],
        ]))
        ->has(WidgetAsset::factory()->asset($assetType), 'assets')
        ->create();

    $layout = (new LayoutFactory)->state([
        'containers' => ['main' => ['widgets' => [['widget_key' => $widget->key]]]],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful();
})->with([AssetEnum::Section, ...Capell\Core\Enums\AssetEnum::cases()]);

// ──────────────────────────────────────────────
// Container operations
// ──────────────────────────────────────────────

test('it adds a container with metadata and persists it', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $originalCount = count($layout->containers);

    $containerKey = array_key_first($layout->containers) . '-2';
    $htmlClass = 'test-class';

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'saveContainer', [[
            'key' => $containerKey,
            'meta' => ['html_class' => $htmlClass],
        ]]))
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'saveLayout'));

    $layout->refresh();

    expect($layout->containers)
        ->toHaveCount($originalCount + 1)
        ->toHaveKey($containerKey)
        ->and($layout->containers[$containerKey]['meta']['html_class'])
        ->toEqual($htmlClass);
});

test('it edits container settings metadata and persists it', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'saveContainer', [
            [
                'key' => $containerKey,
                'meta' => [
                    'alignment' => ContainerAlignmentEnum::Center->value,
                    'hidden_on' => [
                        ResponsiveVisibilityEnum::Mobile->value,
                        ResponsiveVisibilityEnum::Desktop->value,
                    ],
                ],
            ],
            $containerKey,
        ]))
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'saveLayout'));

    $layout->refresh();

    expect($layout->containers[$containerKey]['meta']['alignment'])
        ->toEqual(ContainerAlignmentEnum::Center->value)
        ->and($layout->containers[$containerKey]['meta']['hidden_on'])
        ->toEqual([
            ResponsiveVisibilityEnum::Mobile->value,
            ResponsiveVisibilityEnum::Desktop->value,
        ]);
});

test('it removes a container and persists the change', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $originalCount = count($layout->containers);

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction('removeContainer', arguments: [
            'containerKey' => $containerKey,
        ])
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers)
        ->toHaveCount($originalCount - 1)
        ->not()->toHaveKey($containerKey);
});

test('it edits a container key and preserves widget assets', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    $containerKey = array_key_first($layout->containers);
    $newContainerKey = $containerKey . '-renamed';
    $containerWidget = $layout->containers[$containerKey]['widgets'][0];
    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->count(2)
        ->widget($widget)
        ->occurrence($containerWidget['occurrence'])
        ->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'saveContainer', [
            ['key' => $newContainerKey],
            $containerKey,
        ]))
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'saveLayout'));

    $layout->refresh();

    expect($layout->containers)
        ->toHaveKey($newContainerKey)
        ->not()->toHaveKey($containerKey);

    $assets = WidgetAsset::query()
        ->where('widget_id', $widget->id)
        ->whereNull(['pageable_type', 'pageable_id'])
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

test('it edits a container key for a page layout and updates page asset references', function (string $widgetKey): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    $containerKey = array_key_first($layout->containers);
    $newContainerKey = $containerKey . '-renamed';

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
        'layout' => $layout,
        'page' => $page,
    ])
        ->assertSuccessful()
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'saveContainer', [
            ['key' => $newContainerKey],
            $containerKey,
        ]))
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'saveLayout'));

    expect($layout->refresh()->containers)
        ->toHaveKey($newContainerKey);

    $assets = WidgetAsset::query()
        ->where([
            'pageable_type' => $page->getMorphClass(),
            'pageable_id' => $page->id,
            'widget_id' => $widget->id,
        ])
        ->get();

    expect($assets)
        ->toHaveCount(2)
        ->each(
            fn (Expectation $expect) => $expect
                ->container->toEqual($newContainerKey)
                ->widget_id->toEqual($widget->id)
                ->pageable_type->toEqual($page->getMorphClass())
                ->pageable_id->toEqual($page->id),
        );
})->with(['first', 'second']);

test('it reorders containers', function (): void {
    $widget = Widget::factory()->create(['key' => 'test']);
    $layout = (new LayoutFactory)->state([
        'containers' => [
            'first' => ['widgets' => [['widget_key' => $widget->key]]],
            'second' => ['widgets' => [['widget_key' => $widget->key]]],
        ],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->call('reorderContainers', containerKey: 'second', position: 0)
        ->call('saveLayout');

    $layout->refresh();

    expect(array_keys($layout->containers))->toBe(['second', 'first']);
});

// ──────────────────────────────────────────────
// Widget operations
// ──────────────────────────────────────────────

test('it adds a new widget to a container', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $widget = Widget::factory()->state(['key' => 'new-widget'])->create();
    $containerKey = array_key_first($layout->containers);

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->dispatch('add-widgets-to-container', containerKey: $containerKey, widgets: [$widget->id])
        ->call('saveLayout');

    $layout->refresh();

    $lastWidgetKey = array_key_last($layout->containers[$containerKey]['widgets']);

    expect($layout->containers[$containerKey]['widgets'][$lastWidgetKey])
        ->toBeArray()
        ->toEqual(['widget_key' => $widget->key, 'occurrence' => 1]);
});

test('it increments occurrence when adding an existing widget again', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);

    $lastWidgetKey = array_key_last($layout->containers[$containerKey]['widgets']);
    $lastWidget = $layout->containers[$containerKey]['widgets'][$lastWidgetKey];
    $widget = Widget::query()->firstWhere('key', $lastWidget['widget_key']);

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->dispatch('add-widgets-to-container', containerKey: $containerKey, widgets: [$widget->id])
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

test('it filters widget palette results by widget fields and translation title', function (string $searchTerm, string $expectedWidgetName): void {
    $language = Language::factory()->create();

    $nameMatch = Widget::factory()->state([
        'name' => 'Palette Name Match',
        'key' => 'palette-name-match',
        'order' => 1,
    ])->create();

    $keyMatch = Widget::factory()->state([
        'name' => 'Palette Key Label',
        'key' => 'palette-key-token',
        'order' => 2,
    ])->create();

    $translationMatch = Widget::factory()->state([
        'name' => 'Palette Translation Label',
        'key' => 'palette-translation-label',
        'order' => 3,
    ])->create();

    $translationMatch->translations()->create([
        'language_id' => $language->id,
        'title' => 'Palette Translation Token',
    ]);

    Widget::factory()->state([
        'name' => 'Palette Hidden Result',
        'key' => 'palette-hidden-result',
        'order' => 4,
    ])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => ['main' => ['widgets' => []]],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->set('widgetPaletteSearch', $searchTerm)
        ->assertSet('widgetPalettePage', 1)
        ->assertSeeText($expectedWidgetName)
        ->assertDontSeeText('Palette Hidden Result');
})->with([
    'name' => ['Name Match', 'Palette Name Match'],
    'key' => ['key-token', 'Palette Key Label'],
    'translation title' => ['Translation Token', 'Palette Translation Label'],
]);

test('it paginates widget palette results without navigation', function (): void {
    Widget::factory()->state(['name' => 'Palette Page One A', 'key' => 'palette-page-one-a', 'order' => 1])->create();
    Widget::factory()->state(['name' => 'Palette Page One B', 'key' => 'palette-page-one-b', 'order' => 2])->create();
    Widget::factory()->state(['name' => 'Palette Page Two A', 'key' => 'palette-page-two-a', 'order' => 3])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => ['main' => ['widgets' => []]],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->set('widgetPalettePerPage', 2)
        ->assertSet('widgetPalettePage', 1)
        ->assertSeeText('Palette Page One A')
        ->assertDontSeeText('Palette Page Two A')
        ->call('nextWidgetPalettePage')
        ->assertSet('widgetPalettePage', 2)
        ->assertSeeText('Palette Page Two A')
        ->assertDontSeeText('Palette Page One A')
        ->call('previousWidgetPalettePage')
        ->assertSet('widgetPalettePage', 1)
        ->assertSeeText('Palette Page One A');
});

test('it adds a widget from the palette to a container at a position', function (): void {
    Widget::factory()->state(['key' => 'first'])->create();
    Widget::factory()->state(['key' => 'second'])->create();
    $widget = Widget::factory()->state(['key' => 'palette-inserted'])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'first', 'occurrence' => 1],
                    ['widget_key' => 'second', 'occurrence' => 1],
                ],
            ],
        ],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->call('addPaletteWidgetToContainer', widgetId: $widget->id, containerKey: 'main', position: 1)
        ->call('saveLayout');

    $layout->refresh();

    expect(array_column($layout->containers['main']['widgets'], 'widget_key'))
        ->toBe(['first', 'palette-inserted', 'second'])
        ->and($layout->containers['main']['widgets'][1]['occurrence'])
        ->toBe(1);
});

test('it removes a specific widget from a container and persists the change', function (): void {
    Widget::factory()->state(['key' => 'keep-a'])->create();
    Widget::factory()->state(['key' => 'remove-me'])->create();
    Widget::factory()->state(['key' => 'keep-b'])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'keep-a', 'occurrence' => 1],
                    ['widget_key' => 'remove-me', 'occurrence' => 1],
                    ['widget_key' => 'keep-b', 'occurrence' => 1],
                ],
            ],
        ],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction('removeWidget', arguments: [
            'containerKey' => 'main',
            'widgetIndex' => 1,
        ])
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers['main']['widgets'])
        ->toHaveCount(2)
        ->and(array_column($layout->containers['main']['widgets'], 'widget_key'))
        ->toBe(['keep-a', 'keep-b']);
});

test('it duplicates a widget within the same container', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_last($layout->containers[$containerKey]['widgets']);
    $lastWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];
    $widget = Widget::query()->firstWhere('key', $lastWidget['widget_key']);
    $originalWidgetCount = count($layout->containers[$containerKey]['widgets']);

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction('duplicateWidget', arguments: [
            'containerKey' => $containerKey,
            'widgetIndex' => $widgetIndex,
        ])
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    $layout->refresh();

    $lastWidgetKey = array_key_last($layout->containers[$containerKey]['widgets']);

    expect($layout->containers[$containerKey]['widgets'])
        ->toHaveCount($originalWidgetCount + 1)
        ->and($layout->containers[$containerKey]['widgets'][$lastWidgetKey])
        ->toBeArray()
        ->toEqual([
            'widget_key' => $widget->key,
            'occurrence' => 3,
        ]);
});

test('it duplicates a container with its widgets and meta', function (): void {
    Widget::factory()->state(['key' => 'first'])->create();
    Widget::factory()->state(['key' => 'second'])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'first', 'occurrence' => 1],
                    ['widget_key' => 'second', 'occurrence' => 1],
                ],
                'meta' => [
                    'colspan' => 6,
                ],
            ],
        ],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction('duplicateContainer', arguments: [
            'containerKey' => 'main',
        ])
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers)
        ->toHaveKeys(['main', 'container-2'])
        ->and($layout->containers['container-2']['meta'])
        ->toBe(['colspan' => 6])
        ->and($layout->containers['container-2']['widgets'])
        ->toEqual($layout->containers['main']['widgets']);
});

test('it edits a widget name', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_last($layout->containers[$containerKey]['widgets']);
    $lastWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::query()->firstWhere('key', $lastWidget['widget_key']);
    $newWidget = Widget::factory()->make();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful();

    $widget->update(['name' => $newWidget->name]);

    expect($widget->refresh()->name)->toEqual($newWidget->name);
});

test('it edits layout widget meta via the editLayoutWidget action', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_last($layout->containers[$containerKey]['widgets']);

    $widget = Widget::factory()
        ->for((new WidgetTypeFactory)->state([
            'admin' => [
                'layout_widget_configurator' => DefaultLayoutWidgetConfigurator::getKey(),
            ],
        ]), 'type')
        ->create();

    $widgetIndex++;

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->dispatch('add-widgets-to-container', containerKey: $containerKey, widgets: [$widget->id])
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'editLayoutWidget', [
            $containerKey,
            $widgetIndex,
            ['html_class' => 'foo'],
        ]))
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'saveLayout'));

    $layout->refresh();

    expect($layout->containers[$containerKey]['widgets'][$widgetIndex])
        ->toBeArray()
        ->widget_key->toEqual($widget->key)
        ->occurrence->toEqual(1)
        ->meta->toMatchArray(['html_class' => 'foo']);
});

// ──────────────────────────────────────────────
// Reordering widgets
// ──────────────────────────────────────────────

test('it reorders widgets within the same container', function (): void {
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

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->call('reorderWidgets', containerKey: 'test', containerWidgetIndex: 'test.1', widgetIndex: 0)
        ->call('saveLayout');

    $layout->refresh();

    expect(array_column($layout->containers['test']['widgets'], 'widget_key'))
        ->toBe(['second', 'first']);
});

test('it reorders a newly added widget within the same container', function (): void {
    Widget::factory()->state(['key' => 'first'])->create();
    Widget::factory()->state(['key' => 'second'])->create();
    $widget = Widget::factory()->state(['key' => 'third'])->create();

    $containerKey = 'test';

    $layout = (new LayoutFactory)->state([
        'containers' => [
            $containerKey => [
                'widgets' => [
                    ['widget_key' => 'first', 'occurrence' => 1],
                    ['widget_key' => 'second', 'occurrence' => 1],
                ],
            ],
        ],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->dispatch('add-widgets-to-container', containerKey: $containerKey, widgets: [$widget->id])
        ->call('reorderWidgets', containerKey: $containerKey, containerWidgetIndex: $containerKey . '.2', widgetIndex: 1)
        ->call('saveLayout');

    $layout->refresh();

    expect(array_column($layout->containers[$containerKey]['widgets'], 'widget_key'))
        ->toBe(['first', 'third', 'second']);
});

test('it reorders a widget into a different container', function (): void {
    Widget::factory()->state(['key' => 'first'])->create();
    Widget::factory()->state(['key' => 'second'])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'first', 'occurrence' => 1],
                    ['widget_key' => 'second', 'occurrence' => 1],
                    ['widget_key' => 'first', 'occurrence' => 2],
                ],
            ],
            'sidebar' => [
                'widgets' => [
                    ['widget_key' => 'first', 'occurrence' => 1],
                ],
            ],
        ],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->call('reorderWidgets', containerKey: 'sidebar', containerWidgetIndex: 'main.0', widgetIndex: 1)
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers['main']['widgets'])
        ->toHaveCount(2)
        ->and($layout->containers['main']['widgets'][0])
        ->widget_key->toBe('second')
        ->occurrence->toBe(1)
        ->and($layout->containers['main']['widgets'][1])
        ->widget_key->toBe('first')
        ->occurrence->toBe(1)
        ->and($layout->containers['sidebar']['widgets'])
        ->toHaveCount(2)
        ->and($layout->containers['sidebar']['widgets'][0])
        ->widget_key->toBe('first')
        ->occurrence->toBe(1)
        ->and($layout->containers['sidebar']['widgets'][1])
        ->widget_key->toBe('first')
        ->occurrence->toBe(2);
});

// ──────────────────────────────────────────────
// Layout persistence
// ──────────────────────────────────────────────

test('it saves the layout without modifications preserving existing containers and assets', function (): void {
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

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->set('layoutModified', true)
        ->call('saveLayout');

    expect($layout->refresh()->getAttribute('containers'))
        ->toHaveCount(count($containers))
        ->toEqual($containers);

    expect(
        WidgetAsset::query()
            ->where('widget_id', $widget->id)
            ->whereNull(['pageable_id', 'pageable_type'])
            ->exists(),
    )->toBeTrue();
});

test('it clones the layout and page association', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    livewire(LayoutBuilder::class, [
        'layout' => $layout,
        'page' => $page,
    ])
        ->assertSuccessful()
        ->callAction('duplicateLayoutAction')
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    $clonedLayout = Layout::query()
        ->where('name', $layout->name . ' (2)')
        ->where('key', $layout->key . ' (2)')
        ->first();

    expect($clonedLayout)
        ->toBeInstanceOf(Layout::class)
        ->not()->toBe($layout)
        ->and($clonedLayout->getAttribute('containers'))
        ->toEqual($layout->getAttribute('containers'));
});

test('it skips saving when layoutModified is false', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $originalContainers = $layout->containers;

    $component = livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful();

    $layout->update(['containers' => []]);

    $component->call('saveLayout');

    expect($layout->refresh()->containers)->toEqual([]);

    $layout->update(['containers' => $originalContainers]);

    $component->set('layoutModified', true)->call('saveLayout');

    expect($layout->refresh()->containers)->toEqual($originalContainers);
});

test('it saves layout with notification via saveLayout action', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->set('layoutModified', true)
        ->callAction('saveLayout')
        ->assertNotified();
});

test('it requires update authorization before mounting', function (): void {
    test()->actingAsUser();

    Gate::before(fn (mixed $user, string $ability): ?bool => $ability === 'update' ? false : null);

    $layout = (new LayoutFactory)->containers()->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertForbidden();
});

test('it only persists selected assets allowed for the widget and current site', function (): void {
    $site = Site::factory()->create();
    $otherSite = Site::factory()->create();

    $widget = Widget::factory()
        ->for((new WidgetTypeFactory)->state(['admin' => ['asset_types' => ['page']]]))
        ->create(['key' => 'site-scoped-assets']);

    $layout = (new LayoutFactory)->widgets([$widget])->site($site)->create();
    $page = Page::factory()->site($site)->layout($layout)->create();

    $validPage = Page::factory()->site($site)->create();
    $otherSitePage = Page::factory()->site($otherSite)->create();

    livewire(LayoutBuilder::class, [
        'layout' => $layout,
        'page' => $page,
        'site' => $site,
    ])
        ->assertSuccessful()
        ->call('addAssetsToWidget', [
            'containerKey' => 'main',
            'widgetIndex' => 0,
            'hasPageAssets' => true,
        ], 'page', [
            $validPage->id,
            $otherSitePage->id,
            $page->id,
            999999,
        ])
        ->call('saveLayout');

    expect(WidgetAsset::query()
        ->where('widget_id', $widget->id)
        ->where('asset_type', 'page')
        ->pluck('asset_id')
        ->all())->toEqual([$validPage->id]);
});

test('it ignores browser supplied asset types that are not allowed by the widget', function (): void {
    $site = Site::factory()->create();

    $widget = Widget::factory()
        ->for((new WidgetTypeFactory)->state(['admin' => ['asset_types' => ['section']]]))
        ->create(['key' => 'section-only-assets']);

    $layout = (new LayoutFactory)->widgets([$widget])->site($site)->create();
    $page = Page::factory()->site($site)->layout($layout)->create();
    $pageAsset = Page::factory()->site($site)->create();

    livewire(LayoutBuilder::class, [
        'layout' => $layout,
        'page' => $page,
        'site' => $site,
    ])
        ->assertSuccessful()
        ->call('addAssetsToWidget', [
            'containerKey' => 'main',
            'widgetIndex' => 0,
            'hasPageAssets' => true,
        ], 'page', [$pageAsset->id])
        ->call('saveLayout');

    expect(WidgetAsset::query()
        ->where('widget_id', $widget->id)
        ->exists())->toBeFalse();
});

test('it shows warning when dispatching empty widget list to container', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $originalWidgets = $layout->containers[$containerKey]['widgets'];

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->dispatch('add-widgets-to-container', containerKey: $containerKey, widgets: [])
        ->assertNotified();

    expect($layout->refresh()->containers[$containerKey]['widgets'])
        ->toEqual($originalWidgets);
});

test('it hides duplicate layout action when not in page context', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->assertActionHidden('duplicateLayout');
});

test('it prevents adding a container with a duplicate key', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $existingKey = array_key_first($layout->containers);

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'saveContainer', [[
            'key' => $existingKey,
            'meta' => [],
        ]]));

    expect($layout->refresh()->containers)->toHaveKey($existingKey);
});

test('it edits container metadata without changing the key', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $originalWidgets = $layout->containers[$containerKey]['widgets'];

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'saveContainer', [
            ['key' => $containerKey, 'meta' => ['html_class' => 'updated-class']],
            $containerKey,
        ]))
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'saveLayout'));

    $layout->refresh();

    expect($layout->containers)
        ->toHaveKey($containerKey)
        ->and($layout->containers[$containerKey]['meta']['html_class'])
        ->toEqual('updated-class')
        ->and($layout->containers[$containerKey]['widgets'])
        ->toEqual($originalWidgets);
});

test('it moves a widget between containers and recalculates occurrence', function (): void {
    Widget::factory()->state(['key' => 'alpha'])->create();
    Widget::factory()->state(['key' => 'beta'])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => [
            'source' => [
                'widgets' => [
                    ['widget_key' => 'alpha', 'occurrence' => 1],
                    ['widget_key' => 'beta', 'occurrence' => 1],
                ],
            ],
            'target' => [
                'widgets' => [
                    ['widget_key' => 'alpha', 'occurrence' => 1],
                ],
            ],
        ],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->call('reorderWidgets', containerKey: 'target', containerWidgetIndex: 'source.0', widgetIndex: 0)
        ->call('saveLayout');

    $layout->refresh();

    expect($layout->containers['source']['widgets'])
        ->toHaveCount(1)
        ->and(array_column($layout->containers['source']['widgets'], 'widget_key'))
        ->toBe(['beta'])
        ->and($layout->containers['target']['widgets'])
        ->toHaveCount(2);

    $targetWidgetKeys = array_column($layout->containers['target']['widgets'], 'widget_key');
    expect($targetWidgetKeys)->each->toBe('alpha');

    $targetOccurrences = array_column($layout->containers['target']['widgets'], 'occurrence');
    expect($targetOccurrences)->toBe([1, 2]);
});

test('it increments occurrence correctly across multiple duplications', function (): void {
    $widget = Widget::factory()->state(['key' => 'duped'])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'duped', 'occurrence' => 1],
                ],
            ],
        ],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction('duplicateWidget', arguments: [
            'containerKey' => 'main',
            'widgetIndex' => 0,
        ])
        ->callAction('duplicateWidget', arguments: [
            'containerKey' => 'main',
            'widgetIndex' => 0,
        ])
        ->call('saveLayout');

    $layout->refresh();

    $occurrences = array_column($layout->containers['main']['widgets'], 'occurrence');

    expect($layout->containers['main']['widgets'])
        ->toHaveCount(3)
        ->and($occurrences)
        ->toBe([1, 2, 3]);
});

test('it changes the page layout via changeLayout action', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $newLayout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    livewire(LayoutBuilder::class, [
        'layout' => $layout,
        'page' => $page,
    ])
        ->assertSuccessful()
        ->tap(fn (object $component): null => invokeLayoutBuilderMethod($component, 'changePageLayout', [$newLayout->getKey()]));
});

// ──────────────────────────────────────────────
// Action visibility
// ──────────────────────────────────────────────

test('it hides the addWidget action when layout has no containers', function (): void {
    $layout = (new LayoutFactory)->state(['containers' => []])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->assertActionHidden('addWidget');
});

test('it hides the changeLayout action when not in page context', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->assertActionHidden('changeLayout');
});

// ──────────────────────────────────────────────
// Edge cases
// ──────────────────────────────────────────────

test('it adds multiple widgets to a container at once', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $originalCount = count($layout->containers[$containerKey]['widgets']);

    $widgetA = Widget::factory()->state(['key' => 'batch-a'])->create();
    $widgetB = Widget::factory()->state(['key' => 'batch-b'])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->dispatch('add-widgets-to-container', containerKey: $containerKey, widgets: [$widgetA->id, $widgetB->id])
        ->call('saveLayout');

    $layout->refresh();

    $widgets = $layout->containers[$containerKey]['widgets'];

    expect($widgets)
        ->toHaveCount($originalCount + 2)
        ->and($widgets[array_key_last($widgets) - 1]['widget_key'])->toBe('batch-a')
        ->and($widgets[array_key_last($widgets)]['widget_key'])->toBe('batch-b');
});

test('it removes a widget with assets from the container', function (): void {
    $widgetWithAssets = Widget::factory()->state(['key' => 'has-assets'])->create();
    $widgetWithout = Widget::factory()->state(['key' => 'no-assets'])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'has-assets', 'occurrence' => 1],
                    ['widget_key' => 'no-assets', 'occurrence' => 1],
                ],
            ],
        ],
    ])->create();

    WidgetAsset::factory()
        ->count(3)
        ->widget($widgetWithAssets)
        ->occurrence(1)
        ->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction('removeWidget', arguments: [
            'containerKey' => 'main',
            'widgetIndex' => 0,
        ])
        ->call('saveLayout');

    $layout->refresh();

    $remainingWidgets = array_values($layout->containers['main']['widgets']);

    expect($remainingWidgets)
        ->toHaveCount(1)
        ->and($remainingWidgets[0]['widget_key'])
        ->toBe('no-assets');
});

test('it reorders a container to the last position', function (): void {
    $widget = Widget::factory()->state(['key' => 'test'])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => [
            'first' => ['widgets' => [['widget_key' => 'test']]],
            'second' => ['widgets' => [['widget_key' => 'test']]],
            'third' => ['widgets' => [['widget_key' => 'test']]],
        ],
    ])->create();

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->call('reorderContainers', containerKey: 'first', position: 2)
        ->call('saveLayout');

    $layout->refresh();

    expect(array_keys($layout->containers))->toBe(['second', 'third', 'first']);
});

test('it removes multiple widgets sequentially from a container', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);

    livewire(LayoutBuilder::class, ['layout' => $layout])
        ->assertSuccessful()
        ->callAction('removeWidget', arguments: [
            'containerKey' => $containerKey,
            'widgetIndex' => 2,
        ])
        ->callAction('removeWidget', arguments: [
            'containerKey' => $containerKey,
            'widgetIndex' => 1,
        ])
        ->call('saveLayout');

    $layout->refresh();

    $remainingWidgets = array_values($layout->containers[$containerKey]['widgets']);

    expect($remainingWidgets)
        ->toHaveCount(1)
        ->and($remainingWidgets[0]['widget_key'])
        ->toBe('first');
});
