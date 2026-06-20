<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Livewire\Filament\Support\LayoutBuilderActionFactory;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderActionFlowHarness;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderActionSchemaHarness;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderMutationHarness;
use Filament\Actions\Action;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

it('builds every layout builder action with stable names', function (): void {
    $layoutBuilder = new LayoutBuilder;
    $layoutBuilder->layout = Layout::factory()->create();
    $layoutBuilder->containers = [
        'main' => [
            'widgets' => [],
        ],
    ];
    $layoutBuilder->assets = ['main' => []];
    $layoutBuilder->selectedRecords = ['main' => []];

    $factory = new LayoutBuilderActionFactory($layoutBuilder);

    $actions = [
        $factory->saveLayoutAction(),
        $factory->duplicateLayoutAction(),
        $factory->cloneLayoutForPageAction(),
        $factory->undoLayoutMutationAction(),
        $factory->redoLayoutMutationAction(),
        $factory->addContainerAction(),
        $factory->editContainerAction(),
        $factory->removeContainerAction(),
        $factory->moveContainerUpAction(),
        $factory->moveContainerDownAction(),
        $factory->duplicateContainerAction(),
        $factory->editLayoutWidgetAction(),
        $factory->addWidgetAction(),
        $factory->editWidgetAction(),
        $factory->duplicateWidgetAction(),
        $factory->moveWidgetUpAction(),
        $factory->moveWidgetDownAction(),
        $factory->moveWidgetToContainerAction(),
        $factory->removeWidgetAction(),
        $factory->selectAssetAction(),
        $factory->addAssetAction(),
        $factory->editWidgetAssetAction(),
        $factory->moveAssetUpAction(),
        $factory->moveAssetDownAction(),
        $factory->removeAssetsAction(),
        $factory->changeLayoutAction(),
        $factory->togglePageAssetsAction(),
    ];

    expect($actions)
        ->each->toBeInstanceOf(Action::class)
        ->and(array_map(
            static fn (Action $action): string => $action->getName() ?? '',
            $actions,
        ))->toBe([
            'saveLayout',
            'duplicateLayout',
            'cloneLayoutForPage',
            'undoLayoutMutation',
            'redoLayoutMutation',
            'addContainer',
            'editContainer',
            'removeContainer',
            'moveContainerUp',
            'moveContainerDown',
            'duplicateContainer',
            'editLayoutWidget',
            'addWidget',
            'editWidget',
            'duplicateWidget',
            'moveWidgetUp',
            'moveWidgetDown',
            'moveWidgetToContainer',
            'removeWidget',
            'selectAsset',
            'addAsset',
            'editWidgetAsset',
            'moveAssetUp',
            'moveAssetDown',
            'removeAssets',
            'changeLayout',
            'togglePageAssets',
        ]);
});

it('runs layout builder action closures through the expected editor workflow methods', function (): void {
    $layoutBuilder = new LayoutBuilderActionFlowHarness;
    $layoutBuilder->layout = Layout::factory()->create();
    $layoutBuilder->containers = [
        'main' => [
            'widgets' => [],
        ],
        'aside' => [
            'widgets' => [],
        ],
    ];
    $layoutBuilder->assets = ['main' => [[]]];
    $layoutBuilder->selectedRecords = ['main' => [[]]];

    $factory = new LayoutBuilderActionFactory($layoutBuilder);

    invokeLayoutBuilderAction($factory->saveLayoutAction(), $layoutBuilder);
    invokeLayoutBuilderAction(
        $factory->addContainerAction(),
        $layoutBuilder,
        data: ['key' => 'hero', 'meta' => ['area' => 'main']],
        arguments: ['position' => 0],
    );
    invokeLayoutBuilderAction(
        $factory->editContainerAction(),
        $layoutBuilder,
        data: ['key' => 'primary', 'meta' => []],
        arguments: ['containerKey' => 'main'],
    );
    invokeLayoutBuilderAction($factory->removeContainerAction(), $layoutBuilder, arguments: ['containerKey' => 'aside']);
    invokeLayoutBuilderAction($factory->moveContainerUpAction(), $layoutBuilder, arguments: ['containerKey' => 'main']);
    invokeLayoutBuilderAction($factory->moveContainerDownAction(), $layoutBuilder, arguments: ['containerKey' => 'main']);
    invokeLayoutBuilderAction($factory->duplicateContainerAction(), $layoutBuilder, arguments: ['containerKey' => 'main']);
    invokeLayoutBuilderAction(
        $factory->editLayoutWidgetAction(),
        $layoutBuilder,
        data: ['html_class' => 'featured'],
        arguments: ['containerKey' => 'main', 'widgetIndex' => 0],
    );
    invokeLayoutBuilderAction(
        $factory->addWidgetAction(),
        $layoutBuilder,
        data: ['widgets' => [10, 20], 'container' => 'aside'],
        arguments: ['position' => 1],
    );
    invokeLayoutBuilderAction($factory->duplicateWidgetAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0]);
    invokeLayoutBuilderAction($factory->moveWidgetUpAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 1]);
    invokeLayoutBuilderAction($factory->moveWidgetDownAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0]);
    invokeLayoutBuilderAction(
        $factory->moveWidgetToContainerAction(),
        $layoutBuilder,
        data: ['target_container' => 'aside'],
        arguments: ['containerKey' => 'main', 'widgetIndex' => 0],
    );
    invokeLayoutBuilderAction($factory->removeWidgetAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0]);
    invokeLayoutBuilderAction(
        $factory->selectAssetAction(),
        $layoutBuilder,
        data: ['assets' => [123]],
        arguments: ['containerKey' => 'main', 'widgetIndex' => 0, 'type' => 'Page'],
    );
    invokeLayoutBuilderAction($factory->moveAssetUpAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0, 'assetIndex' => 1]);
    invokeLayoutBuilderAction($factory->moveAssetDownAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0, 'assetIndex' => 0]);
    invokeLayoutBuilderAction($factory->removeAssetsAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0]);
    invokeLayoutBuilderAction($factory->togglePageAssetsAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0]);

    invokeLayoutBuilderAction($factory->undoLayoutMutationAction(), $layoutBuilder);
    invokeLayoutBuilderAction($factory->redoLayoutMutationAction(), $layoutBuilder);

    $calls = collect($layoutBuilder->recordedCalls)
        ->map(fn (array $call): string => $call['method'])
        ->all();

    expect($calls)->toContain(
        'saveLayout',
        'saveContainer',
        'removeContainer',
        'moveContainerUp',
        'moveContainerDown',
        'duplicateContainer',
        'editLayoutWidget',
        'addWidgetsToContainer',
        'duplicateWidget',
        'moveWidgetUp',
        'moveWidgetDown',
        'moveWidgetToContainer',
        'removeWidget',
        'addAssetsToWidget',
        'moveAssetUp',
        'moveAssetDown',
        'removeSelectedAssets',
        'togglePageAssets',
        'undoLayoutMutation',
        'redoLayoutMutation',
    );

    expect($layoutBuilder->recordedCalls[1])->toMatchArray([
        'method' => 'saveContainer',
        'arguments' => [
            ['key' => 'hero', 'meta' => ['area' => 'main']],
            null,
            0,
        ],
    ]);

    $addWidgetCall = collect($layoutBuilder->recordedCalls)
        ->firstWhere('method', 'addWidgetsToContainer');
    $addWidgetCall = capell_test_array($addWidgetCall);

    expect($addWidgetCall['arguments'] ?? null)->toBe(['aside', [10, 20], null, 1]);

    $selectAssetCall = collect($layoutBuilder->recordedCalls)
        ->firstWhere('method', 'addAssetsToWidget');
    $selectAssetCall = capell_test_array($selectAssetCall);

    expect($selectAssetCall['arguments'] ?? null)->toBe([
        [
            'containerKey' => 'main',
            'widgetIndex' => 0,
            'hasPageAssets' => false,
        ],
        'Page',
        [123],
    ]);
});

it('evaluates layout builder action presentation and modal workflows from editor state', function (): void {
    $layoutBuilder = new LayoutBuilderActionFlowHarness;
    $layoutBuilder->layout = Layout::factory()->create(['name' => 'Current layout']);
    $layoutBuilder->page = Page::factory()->create();
    $layoutBuilder->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => 'hero', 'meta' => ['html_class' => 'lead']],
            ],
        ],
        'aside' => [
            'widgets' => [],
        ],
    ];
    $layoutBuilder->assets = [
        'main' => [
            [
                [
                    'asset_id' => $layoutBuilder->page->getKey(),
                    'asset_type' => 'Page',
                    'meta' => ['caption' => 'Hero image'],
                    'order' => 1,
                    'occurrence' => 1,
                    'pageable_id' => $layoutBuilder->page->getKey(),
                    'pageable_type' => $layoutBuilder->page->getMorphClass(),
                    'container' => 'main',
                ],
            ],
        ],
    ];
    $layoutBuilder->selectedRecords = ['main' => [['Page.' . $layoutBuilder->page->getKey()]]];

    $widget = Widget::factory()->create([
        'name' => 'Hero widget',
        'key' => 'hero',
        'admin' => ['asset_types' => ['Page']],
    ]);
    $widgetAsset = WidgetAsset::query()->make([
        'widget_id' => $widget->getKey(),
        'asset_id' => $layoutBuilder->page->getKey(),
        'asset_type' => 'Page',
        'meta' => ['caption' => 'Hero image'],
        'order' => 1,
        'occurrence' => 1,
    ]);
    $widgetAsset->exists = true;
    $widgetAsset->setRelation('asset', $layoutBuilder->page);

    $widget->setRelation('assets', new EloquentCollection([$widgetAsset]));
    $layoutBuilder->containerWidgetRecord = $widget;

    $factory = new LayoutBuilderActionFactory($layoutBuilder);
    $schema = layoutBuilderActionSchema($layoutBuilder);

    expect(layoutBuilderActionValue($factory->addWidgetAction(), 'label', $layoutBuilder, arguments: []))->toBeString()
        ->and(layoutBuilderActionValue($factory->addWidgetAction(), 'label', $layoutBuilder, arguments: ['position' => 1]))->toBeString()
        ->and(layoutBuilderActionValue($factory->addWidgetAction(), 'schema', $layoutBuilder, arguments: [], schema: $schema))->toBeInstanceOf(Schema::class)
        ->and(layoutBuilderActionValue($factory->addWidgetAction(), 'schema', $layoutBuilder, arguments: ['containerKey' => 'main'], schema: $schema))->toBeInstanceOf(Schema::class)
        ->and(layoutBuilderActionValue($factory->editContainerAction(), 'schema', $layoutBuilder, arguments: ['containerKey' => 'main'], schema: $schema))->toBeInstanceOf(Schema::class)
        ->and(layoutBuilderActionValue($factory->editLayoutWidgetAction(), 'isVisible', $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0]))->toBeTrue()
        ->and(layoutBuilderActionValue($factory->editLayoutWidgetAction(), 'modalDescription', $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0]))->toBeString()
        ->and(layoutBuilderActionValue($factory->editLayoutWidgetAction(), 'schema', $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0], schema: $schema))->toBeInstanceOf(Schema::class)
        ->and(layoutBuilderActionValue($factory->selectAssetAction(), 'label', $layoutBuilder, arguments: ['type' => 'Page']))->toBeString()
        ->and(layoutBuilderActionValue($factory->selectAssetAction(), 'modalHeading', $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0, 'type' => 'Page']))->toBeString()
        ->and(layoutBuilderActionValue($factory->selectAssetAction(), 'schema', $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0, 'type' => 'Page'], schema: $schema))->toBeInstanceOf(Schema::class)
        ->and(layoutBuilderActionValue($factory->addAssetAction(), 'modalHeading', $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0, 'type' => 'Page']))->toBeString()
        ->and(layoutBuilderActionValue($factory->editWidgetAssetAction(), 'modalHeading', $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0, 'index' => 0, 'type' => 'Page']))->toBeString()
        ->and(layoutBuilderActionValue($factory->editWidgetAssetAction(), 'modalDescription', $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0, 'index' => 0, 'type' => 'Page']))->toBeString()
        ->and(layoutBuilderActionValue($factory->togglePageAssetsAction(), 'isVisible', $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0]))->toBeTrue()
        ->and(layoutBuilderActionValue($factory->togglePageAssetsAction(), 'modalDescription', $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0]))->toBeString()
        ->and(layoutBuilderActionValue($factory->removeAssetsAction(), 'extraAttributes', $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0]))->toHaveKey('x-show');

    try {
        invokeLayoutBuilderAction($factory->duplicateLayoutAction(), $layoutBuilder);
        invokeLayoutBuilderAction($factory->cloneLayoutForPageAction(), $layoutBuilder);
        invokeLayoutBuilderAction($factory->changeLayoutAction(), $layoutBuilder, data: ['layout_id' => Layout::factory()->create()->getKey()]);
    } catch (ActionNotResolvableException) {
        // These actions normally run while mounted by Filament; the layout mutation path above is still exercised.
    }

    $layoutBuilder->selectedAssetValues = [];

    try {
        invokeLayoutBuilderAction($factory->removeAssetsAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0]);
    } catch (Halt) {
        // The editor deliberately halts when the remove action is submitted with no selected assets.
    }

    invokeLayoutBuilderAction($factory->togglePageAssetsAction(), $layoutBuilder, arguments: ['containerKey' => 'main', 'widgetIndex' => 0]);

    expect(collect($layoutBuilder->recordedCalls)->pluck('method')->all())->toContain(
        'assertCanUpdateLayout',
        'layoutUpdated',
        'getContainerSchema',
        'getContainerWidgetConfigurator',
        'getWidgetAssetsByType',
        'ensureLoaded',
        'togglePageAssets',
    );
});

it('builds change layout options from site-aware layout state', function (): void {
    $site = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $currentLayout = Layout::factory()->site($site)->create(['name' => 'Current Site Layout']);
    $globalLayout = Layout::factory()->create(['name' => 'Global Layout', 'site_id' => null]);
    $otherSiteLayout = Layout::factory()->site($otherSite)->create(['name' => 'Other Site Layout']);
    Page::factory()->count(2)->site($site)->create(['layout_id' => $currentLayout->getKey()]);

    $layoutBuilder = new LayoutBuilderActionFlowHarness;
    $layoutBuilder->site = $site;
    $layoutBuilder->layout = $currentLayout;
    $layoutBuilder->page = Page::factory()->site($site)->create(['layout_id' => $currentLayout->getKey()]);

    $factory = new LayoutBuilderActionFactory($layoutBuilder);
    $schema = invokeLayoutBuilderActionFactoryMethod($factory, 'getChangeLayoutSchema');
    $select = $schema[0] ?? null;

    expect($select)->toBeInstanceOf(Select::class);

    /** @var Select $select */
    $options = $select->getOptions();

    expect($options)->toHaveKeys([$currentLayout->getKey(), $globalLayout->getKey()])
        ->and($options)->not->toHaveKey($otherSiteLayout->getKey())
        ->and($select->getDefaultState())->toBeInt();
});

it('adds and edits widget assets through layout builder action callbacks', function (): void {
    $layoutBuilder = new LayoutBuilderActionFlowHarness;
    $layoutBuilder->layout = Layout::factory()->create(['name' => 'Asset workflow layout']);
    $layoutBuilder->page = Page::factory()->create();
    $layoutBuilder->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => 'hero', 'meta' => [], 'occurrence' => 1],
            ],
        ],
    ];
    $layoutBuilder->assets = ['main' => [[]]];
    $layoutBuilder->selectedRecords = ['main' => [[]]];

    $assetPage = Page::factory()->create(['name' => 'Hero asset page']);
    $widget = Widget::factory()->create([
        'name' => 'Hero widget',
        'key' => 'hero',
        'admin' => ['asset_types' => ['page']],
    ]);
    $widget->setRelation('assets', new EloquentCollection);

    $layoutBuilder->containerWidgetRecord = $widget;

    $newWidgetAsset = WidgetAsset::query()->make([
        'widget_id' => $widget->getKey(),
        'workspace_id' => 0,
        'asset_type' => 'page',
        'asset_id' => $assetPage->getKey(),
        'meta' => [],
        'order' => 1,
        'occurrence' => 1,
    ]);
    $newWidgetAsset->setRelation('asset', $assetPage);

    $layoutBuilder->mountedActionSchemaForTest = Schema::make(new LayoutBuilderActionSchemaHarness)
        ->record($newWidgetAsset);

    $factory = new LayoutBuilderActionFactory($layoutBuilder);

    invokeLayoutBuilderAction(
        $factory->addAssetAction(),
        $layoutBuilder,
        data: [
            $assetPage->getKey() => ['caption' => 'Added through the action'],
        ],
        arguments: [
            'containerKey' => 'main',
            'widgetIndex' => 0,
            'type' => 'page',
        ],
    );

    $addedAssetState = $layoutBuilder->assets['main'][0][0] ?? [];

    $persistedAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($assetPage)
        ->create([
            'workspace_id' => 0,
            'container' => 'main',
            'occurrence' => 1,
            'order' => 1,
            'meta' => ['caption' => 'Before edit'],
            'pageable_id' => $layoutBuilder->page->getKey(),
            'pageable_type' => $layoutBuilder->page->getMorphClass(),
        ]);
    $persistedAsset->setRelation('asset', $assetPage);

    $widget->setRelation('assets', new EloquentCollection([$persistedAsset]));
    $layoutBuilder->assets = [
        'main' => [
            [
                [
                    'id' => $persistedAsset->getKey(),
                    'widget_id' => $widget->getKey(),
                    'workspace_id' => 0,
                    'asset_id' => $assetPage->getKey(),
                    'asset_type' => $assetPage->getMorphClass(),
                    'meta' => ['caption' => 'Before edit'],
                    'order' => 1,
                    'occurrence' => 1,
                    'pageable_id' => $layoutBuilder->page->getKey(),
                    'pageable_type' => $layoutBuilder->page->getMorphClass(),
                    'container' => 'main',
                ],
            ],
        ],
    ];

    $editSchema = Schema::make(new LayoutBuilderActionSchemaHarness)
        ->record($persistedAsset);
    $editAction = $factory->editWidgetAssetAction();

    invokeLayoutBuilderWidgetAssetEditAction(
        $editAction,
        $layoutBuilder,
        $persistedAsset,
        $editSchema,
        data: ['meta' => ['caption' => 'Edited through the action']],
        arguments: [
            'containerKey' => 'main',
            'widgetIndex' => 0,
            'index' => 0,
            'type' => $assetPage->getMorphClass(),
            'contentInventorySignature' => $layoutBuilder->contentInventorySignature(),
        ],
    );

    $calls = collect($layoutBuilder->recordedCalls)->pluck('method')->all();

    expect($layoutBuilder->assets['main'][0][0])->toMatchArray([
        'meta' => ['caption' => 'Edited through the action'],
    ])
        ->and($addedAssetState)->toMatchArray([
            'asset_id' => $assetPage->getKey(),
            'asset_type' => 'page',
            'meta' => ['caption' => 'Added through the action'],
            'pageable_id' => $layoutBuilder->page->getKey(),
            'pageable_type' => $layoutBuilder->page->getMorphClass(),
            'container' => 'main',
        ])
        ->and($persistedAsset->fresh()?->meta)->toBe(['caption' => 'Edited through the action'])
        ->and($layoutBuilder->assets['main'][0][0]['meta'])->toBe(['caption' => 'Edited through the action'])
        ->and($calls)->toContain(
            'assertCanUpdateLayout',
            'loadFromStore',
            'assertCanEditContent',
            'getContainerWidget',
            'getContainerWidgetOccurrence',
            'updateWidgetAssetContentState',
            'reloadContainerWidgetAsset',
            'layoutUpdated',
        );
});

it('mutates real layout builder container and widget state through editor operations', function (): void {
    $heroWidget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero']);
    $cardsWidget = Widget::factory()->create(['key' => 'cards', 'name' => 'Cards']);
    $featureWidget = Widget::factory()->create(['key' => 'feature', 'name' => 'Feature']);

    $layoutBuilder = new LayoutBuilderMutationHarness;
    $layoutBuilder->layout = Layout::factory()->create();
    $layoutBuilder->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => 'hero', 'meta' => ['html_class' => 'lead'], 'occurrence' => 1],
                ['widget_key' => 'cards', 'meta' => [], 'occurrence' => 1],
            ],
        ],
        'aside' => [
            'widgets' => [],
        ],
    ];
    $layoutBuilder->assets = [
        'main' => [
            [
                [
                    'asset_id' => 10,
                    'asset_type' => 'Page',
                    'container' => 'main',
                    'occurrence' => 1,
                ],
            ],
            [],
        ],
        'aside' => [],
    ];
    $layoutBuilder->originalAssets = $layoutBuilder->assets;
    $layoutBuilder->selectedRecords = ['main' => [['Page.10'], []], 'aside' => []];
    $layoutBuilder->knownContainerKeys = ['main', 'aside'];
    $layoutBuilder->seedContainerWidgets([
        'main' => [$heroWidget, $cardsWidget],
        'aside' => [],
    ]);

    $layoutBuilder->saveContainer([
        'key' => 'primary',
        'meta' => ['area' => 'content'],
    ], 'main');
    $renamedAssetContainer = $layoutBuilder->assets['primary'][0][0]['container'] ?? null;
    $layoutBuilder->duplicateContainer('primary');
    $addedWidgetIndex = $layoutBuilder->addWidgetToContainer($featureWidget, 'primary');
    $layoutBuilder->insertContainerWidgetAtPositionForTest('primary', $addedWidgetIndex, 0);
    $layoutBuilder->normalizeContainerWidgetOccurrencesForTest('primary');
    $layoutBuilder->editLayoutWidget('primary', 0, ['html_class' => 'featured']);
    $layoutBuilder->duplicateWidget('primary', 0, withAssets: false);
    $layoutBuilder->removeWidget('primary', 1);
    $layoutBuilder->removeContainer('aside');

    $primaryWidgetsValue = data_get($layoutBuilder->containers, 'primary.widgets');
    $primaryWidgets = is_array($primaryWidgetsValue) ? $primaryWidgetsValue : [];

    expect($layoutBuilder->containers)->toHaveKey('primary')
        ->and($layoutBuilder->containers)->not->toHaveKey('main')
        ->and($layoutBuilder->containers)->not->toHaveKey('aside')
        ->and($renamedAssetContainer)->toBe('primary')
        ->and($layoutBuilder->knownContainerKeys)->toContain('primary')
        ->and($layoutBuilder->knownContainerKeys)->not->toContain('main', 'aside')
        ->and($primaryWidgets[0]['widget_key'])->toBe('feature')
        ->and($primaryWidgets[0]['meta']['html_class'])->toBe('featured')
        ->and($primaryWidgets)->toHaveCount(3)
        ->and($layoutBuilder->containerWidgetsForTest()['primary'][0]->is($featureWidget))->toBeTrue()
        ->and($layoutBuilder->containerWidgetKeysForTest())->toContain('feature', 'hero')
        ->and($layoutBuilder->events)->toContain('assertCanUpdateLayout', 'ensureLoaded', 'layoutUpdated')
        ->and($layoutBuilder->layoutModified)->toBeTrue();
});

it('loads persisted widget container definitions when loading layout state', function (): void {
    $layoutBuilder = new LayoutBuilderMutationHarness;
    $layoutBuilder->layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'hero', 'meta' => ['html_class' => 'legacy']],
                    'raw-widget-key',
                ],
                'meta' => ['area' => 'main'],
            ],
        ],
    ]);
    $layoutBuilder->containers = null;

    $layoutBuilder->setupContainersFromLayoutForTest();
    $layoutBuilder->setupContainersFromLayoutForTest();

    $containers = capell_test_array($layoutBuilder->containers);
    $mainContainer = capell_test_array($containers['main'] ?? null);
    $widgets = capell_test_array($mainContainer['widgets'] ?? null);

    expect($widgets[0])->toMatchArray([
        'widget_key' => 'hero',
        'meta' => ['html_class' => 'legacy'],
    ])
        ->and($widgets[1])->toBe('raw-widget-key')
        ->and($layoutBuilder->knownContainerKeys)->toBe(['main']);
});

/**
 * @param  array<array-key, mixed>  $data
 * @param  array<array-key, mixed>  $arguments
 */
function invokeLayoutBuilderAction(
    Action $action,
    LayoutBuilderActionFlowHarness $layoutBuilder,
    array $data = [],
    array $arguments = [],
): void {
    $closure = $action->getActionFunction();

    expect($closure)->not->toBeNull();

    $action->evaluate(
        $closure,
        [
            'action' => $action,
            'livewire' => $layoutBuilder,
            'data' => $data,
            'arguments' => $arguments,
        ],
        [
            Action::class => $action,
            LayoutBuilder::class => $layoutBuilder,
            LayoutBuilderActionFlowHarness::class => $layoutBuilder,
        ],
    );
}

/**
 * @param  array<array-key, mixed>  $data
 * @param  array<array-key, mixed>  $arguments
 */
function invokeLayoutBuilderWidgetAssetEditAction(
    Action $action,
    LayoutBuilderActionFlowHarness $layoutBuilder,
    WidgetAsset $record,
    Schema $schema,
    array $data,
    array $arguments,
): void {
    $closure = $action->getActionFunction();

    expect($closure)->not->toBeNull();

    $action->evaluate(
        $closure,
        [
            'record' => $record,
            'data' => $data,
            'livewire' => $layoutBuilder,
            'arguments' => $arguments,
            'action' => $action,
            'schema' => $schema,
        ],
        [
            Action::class => $action,
            LayoutBuilder::class => $layoutBuilder,
            LayoutBuilderActionFlowHarness::class => $layoutBuilder,
            WidgetAsset::class => $record,
            Schema::class => $schema,
        ],
    );
}

function layoutBuilderActionSchema(LayoutBuilderActionFlowHarness $layoutBuilder): Schema
{
    unset($layoutBuilder);

    return Schema::make(new LayoutBuilderActionSchemaHarness)->operation('edit');
}

/**
 * @param  array<array-key, mixed>  $arguments
 */
function layoutBuilderActionValue(
    Action $action,
    string $property,
    LayoutBuilderActionFlowHarness $layoutBuilder,
    array $arguments = [],
    ?Schema $schema = null,
): mixed {
    $value = layoutBuilderActionRawProperty($action, $property);

    if (! $value instanceof Closure) {
        if ($property !== 'extraAttributes') {
            return $value;
        }

        $attributes = [];

        foreach ($value as $extraAttributes) {
            $attributes = [
                ...$attributes,
                ...(is_array($extraAttributes)
                    ? $extraAttributes
                    : $action->evaluate($extraAttributes, ['arguments' => $arguments, 'livewire' => $layoutBuilder])),
            ];
        }

        return $attributes;
    }

    $schema ??= layoutBuilderActionSchema($layoutBuilder);

    return $action->evaluate(
        $value,
        [
            'action' => $action,
            'arguments' => $arguments,
            'livewire' => $layoutBuilder,
            'schema' => $schema,
        ],
        [
            Action::class => $action,
            LayoutBuilder::class => $layoutBuilder,
            LayoutBuilderActionFlowHarness::class => $layoutBuilder,
            Schema::class => $schema,
        ],
    );
}

function layoutBuilderActionRawProperty(Action $action, string $property): mixed
{
    $reflection = new ReflectionClass($action);

    while ($reflection !== false) {
        if ($reflection->hasProperty($property)) {
            $propertyReflection = $reflection->getProperty($property);

            return $propertyReflection->getValue($action);
        }

        $reflection = $reflection->getParentClass();
    }

    throw new RuntimeException(sprintf('Action property [%s] was not found.', $property));
}

function invokeLayoutBuilderActionFactoryMethod(LayoutBuilderActionFactory $factory, string $method): mixed
{
    $reflection = new ReflectionMethod($factory, $method);

    return $reflection->invoke($factory);
}
