<?php

declare(strict_types=1);

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Type;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Livewire\LayoutBuilder;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Str;
use Pest\Expectation;
use Pest\Expectations\HigherOrderExpectation;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

/**
 * @property Layout $layout
 */
beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('Can save without affecting widget assets', function (bool $withPage): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $widgetKey = $layout->containers[$containerKey]['widgets'][$widgetIndex]['widget_key'];

    $widget = Widget::query()->firstWhere('key', $widgetKey);
    WidgetAsset::factory()->count(2)->create();
    Content::factory()->count(2)->create();
    Page::factory()->count(3)->create();

    WidgetAsset::factory()->count(5)->widget($widget)->create();

    expect($widget->widgetAssets()->count())
        ->toBe(5);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $withPage ? $page->id : null,
    ])
        ->assertSuccessful()
        ->call('saveLayout')
        ->assertHasNoFormErrors();

    expect($widget->widgetAssets()->count())
        ->toBe(5);
})->with(['with page' => true, 'without page' => false]);

test('Can sync new widget assets to page layout', function (): void {
    $firstWidget = Widget::factory(['key' => 'first'])->create();
    $secondWidget = Widget::factory(['key' => 'second'])->create();

    $layout = (new LayoutFactory)->state([
        'containers' => fn (): array => [
            'main' => [
                'widgets' => [
                    [
                        'widget_key' => $firstWidget->key,
                        'occurrence' => 1,
                    ],
                    [
                        'widget_key' => $secondWidget->key,
                        'occurrence' => 1,
                    ],
                    [
                        'widget_key' => $firstWidget->key,
                        'occurrence' => 2,
                    ],
                ],
                'meta' => [],
            ],
        ],
    ])
        ->create();

    $page = Page::factory()->layout($layout)->create();

    $containerKey = 'main';

    // 2 existing
    WidgetAsset::factory()
        ->count(2)
        ->widget($firstWidget)
        ->page($page, $containerKey, 1)
        ->create();

    // 5 to add
    $contents = Content::factory()->count(2)->create();
    $pages = Page::factory()->count(3)->create();

    // Excluded
    WidgetAsset::factory()
        ->count(3)
        ->create();

    expect($firstWidget->pageAssets($page, $containerKey, 1)->count())
        ->toBe(2);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $page->id,
    ])
        ->assertSuccessful()
        ->call(
            'addAssetsToWidget',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => 0,
                'hasPageAssets' => true,
            ],
            type: \Capell\Layout\Enums\AssetEnum::Content->value,
            assets: $contents->map(fn (Content $record): string => (string) $record->id)->all(),
        )
        ->call(
            'addAssetsToWidget',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => 0,
                'hasPageAssets' => true,
            ],
            type: AssetEnum::Page->value,
            assets: $pages->map(fn (Page $record): string => (string) $record->id)->all(),
        )
        ->call('saveLayout');

    expect($firstWidget->widgetAssets()->count())
        ->toBe(0)
        ->and($firstWidget->pageAssets($page, $containerKey, 1)->count())
        ->toBe(7)
        ->and($secondWidget->assets()->count())
        ->toBe(0);
});

test('Can sync new widget assets to layout', function (): void {
    $layout = (new LayoutFactory)->containers()->create();

    // 5 to add
    $contents = Content::factory()->count(2)->create();
    $pages = Page::factory()->count(3)->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $occurrence = $layout->containers[$containerKey]['widgets'][$widgetIndex]['occurrence'];
    $widget = Widget::query()->firstWhere('key', 'first');

    // 2 existing
    WidgetAsset::factory()
        ->count(2)
        ->widget($widget)
        ->create();

    // 3 excluded
    WidgetAsset::factory()
        ->count(3)
        ->create();

    expect($widget->widgetAssets()->get())
        ->toHaveCount(2)
        ->each(
            fn (Expectation $expectation): HigherOrderExpectation => $expectation
                ->container->toBeNull()
                ->occurrence->toBe($occurrence),
        );

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->call(
            'addAssetsToWidget',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'hasPageAssets' => false,
            ],
            type: \Capell\Layout\Enums\AssetEnum::Content->value,
            assets: $contents->map(fn (Content $record): string => (string) $record->id)->all(),
        )
        ->call(
            'addAssetsToWidget',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'hasPageAssets' => false,
            ],
            type: AssetEnum::Page->value,
            assets: $pages->map(fn (Page $record): string => (string) $record->id)->all(),
        )
        ->call('saveLayout');

    expect($widget->widgetAssets()->where('occurrence', $occurrence)->count())
        ->toBe(7);
});

test('Can sync new page assets', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $widgetKey = $layout->containers[$containerKey]['widgets'][$widgetIndex]['widget_key'];
    $occurrence = $layout->containers[$containerKey]['widgets'][$widgetIndex]['occurrence'];

    $widget = Widget::query()->firstWhere('key', $widgetKey);

    $contents = Content::factory()->count(2)->create();
    $pages = Page::factory()->count(3)->create();

    WidgetAsset::factory()
        ->count(2)
        ->widget($widget)
        ->page($page, $containerKey, $occurrence)
        ->create();

    // Excluded
    WidgetAsset::factory()->count(3)->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $page->id,
    ])
        ->assertSuccessful()
        ->call(
            'addAssetsToWidget',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'hasPageAssets' => true,
            ],
            type: \Capell\Layout\Enums\AssetEnum::Content->value,
            assets: $contents->map(fn (Content $record): string => (string) $record->id)->all(),
        )
        ->call(
            'addAssetsToWidget',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'hasPageAssets' => true,
            ],
            type: AssetEnum::Page->value,
            assets: $pages->map(fn (Page $record): string => (string) $record->id)->all(),
        )
        ->call('saveLayout');

    expect($widget
        ->assets()
        ->where('page_id', $page->id)
        ->count())
        ->toBe(7);
});

test('Can reorder assets', function (): void {
    $widget = Widget::factory()->create();

    $layout = (new LayoutFactory)->state([
        'containers' => [
            'test' => [
                'widgets' => [
                    ['widget_key' => $widget->key, 'occurrence' => 1],
                    ['widget_key' => $widget->key, 'occurrence' => 2],
                ],
            ],
        ],
    ])->create();

    $secondAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset(\Capell\Layout\Enums\AssetEnum::Content)
        ->state([
            'order' => 2,
            'occurrence' => 2,
        ])
        ->create();

    $firstAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset(AssetEnum::Page)
        ->state([
            'order' => 1,
            'occurrence' => 2,
        ])
        ->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->call(
            'reorderAssets',
            containerKey: 'test',
            widgetIndex: 1,
            index: 1,
            newIndex: 0,
        )
        ->call('saveLayout');

    expect($secondAsset->refresh())
        ->order->toBe(1);

    expect($firstAsset->refresh())
        ->order->toBe(2);
});

test('Can select all widget assets', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    foreach ([AssetEnum::Page, Capell\Layout\Enums\AssetEnum::Content] as $assetType) {
        WidgetAsset::factory()
            ->count(2)
            ->widget($widget)
            ->asset($assetType)
            ->container($containerKey)
            ->state(['occurrence' => 1, 'page_id' => null])
            ->create();
    }

    $assets = $widget->widgetAssets()->ordered()->get();

    $emptySelectedRecords = [];
    $selectedRecords = [];

    foreach ($layout->containers[$containerKey]['widgets'] as $i => $widget) {
        $emptySelectedRecords[$containerKey][$i] = [];

        if ($i !== $widgetIndex) {
            $selectedRecords[$containerKey][$i] = [];

            continue;
        }

        $selectedRecords[$containerKey][$i] = $assets->map(
            fn (WidgetAsset $layoutAsset): string => $layoutAsset->asset_key,
        )->toArray();
    }

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->call('selectAllAssets', containerKey: $containerKey, widgetIndex: 0)
        ->assertSet('selectedRecords', $selectedRecords)
        ->call('deSelectAllAssets', containerKey: $containerKey, widgetIndex: 0)
        ->assertSet('selectedRecords', $emptySelectedRecords);
});

test('can add page asset', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $newData = Page::factory()->make();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->mountAction(
            TestAction::make('addAsset')
                ->arguments([
                    'containerKey' => $containerKey,
                    'widgetIndex' => $widgetIndex,
                    'type' => 'page',
                ]),
        )
        ->fillForm([
            'asset' => [
                'layout_id' => $newData->layout_id,
                'site_id' => $newData->site_id,
                'name' => $newData->name,
            ],
        ])
        ->set('mountedActions.0.data.asset.translations', [
            (string) Str::uuid() => [
                'title' => $newData->name,
                'slug' => Str::slug($newData->name),
                'language_id' => $newData->site->language_id,
            ],
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    assertDatabaseHas('pages', [
        'name' => $newData->name,
    ]);

    assertDatabaseHas('widget_assets', [
        'page_id' => null,
        'widget_id' => $widget->id,
        'container' => null,
        'occurrence' => 1,
        'asset_type' => 'page',
    ]);
});

test('can add page asset to existing widget with page layout', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $pageLayout = Page::factory()
        ->layout($layout)
        ->create();

    $newData = Page::factory()->make();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->widget($widget)
        ->page($pageLayout, $containerKey, $containerWidget['occurrence'])
        ->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $pageLayout->id,
    ])
        ->assertSuccessful()
        ->assertActionExists('addAsset')
        ->mountAction(
            TestAction::make('addAsset')->arguments(
                [
                    'containerKey' => $containerKey,
                    'widgetIndex' => $widgetIndex,
                    'type' => 'page',
                ],
            ),
        )
        ->fillForm([
            'asset' => [
                'layout_id' => $newData->layout_id,
                'site_id' => $newData->site_id,
                'name' => $newData->name,
            ],
        ])
        ->set('mountedActions.0.data.asset.translations', [
            (string) Str::uuid() => [
                'title' => $newData->name,
                'slug' => Str::slug($newData->name),
                'language_id' => $newData->site->language_id,
            ],
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    assertDatabaseHas('pages', [
        'name' => $newData->name,
    ]);

    assertDatabaseHas('widget_assets', [
        'page_id' => $pageLayout->id,
        'widget_id' => $widget->id,
        'container' => $containerKey,
        'occurrence' => $containerWidget['occurrence'],
        'asset_type' => 'page',
    ]);
});

test('can add page asset to widget with page layout', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $pageLayout = Page::factory()
        ->layout($layout)
        ->create();

    Type::factory()->type(LayoutTypeEnum::Widget)->group('page')->create();
    Type::factory()->type(LayoutTypeEnum::Widget)->group('assets')->create();

    $newData = Page::factory()->make();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $pageLayout->id,
    ])
        ->assertSuccessful()
        ->mountAction(
            'addAsset',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'type' => 'page',
            ],
        )
        ->fillForm([
            'asset' => [
                'layout_id' => $newData->layout_id,
                'site_id' => $newData->site_id,
                'name' => $newData->name,
            ],
        ])
        ->set('mountedActions.0.data.asset.translations', [
            (string) Str::uuid() => [
                'title' => $newData->name,
                'slug' => Str::slug($newData->name),
                'language_id' => $newData->site->language_id,
            ],
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    assertDatabaseHas('pages', [
        'name' => $newData->name,
    ]);

    assertDatabaseHas('widget_assets', [
        'page_id' => $pageLayout->id,
        'widget_id' => $widget->id,
        'container' => $containerKey,
        'occurrence' => $containerWidget['occurrence'],
        'asset_type' => 'page',
    ]);
});

test('can select assets', function (string $assetType): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    livewire(LayoutBuilder::class, ['layout_id' => $layout->id])
        ->assertSuccessful()
        ->mountAction(
            TestAction::make('selectAsset')
                ->arguments([
                    'containerKey' => $containerKey,
                    'widgetIndex' => $widgetIndex,
                    'type' => $assetType,
                ]),
        )
        ->callMountedAction()
        ->assertHasNoFormErrors();
})->with(['page', 'content']);

test('can edit asset', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    $layoutAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset(AssetEnum::Page)
        ->create();

    $page = $layoutAsset->asset()->with('translation')->first();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->mountAction(
            'editWidgetAsset',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'index' => 0,
                'type' => $layoutAsset['asset_type'],
            ],
        )
        ->fillForm([
            'asset.translations.record-' . $page->translation->id . '.title' => 'testing',
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors();

    expect($layoutAsset->refresh())
        ->asset->translation->title->toBe('testing');
});

test('can remove widget assets', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->widget($widget)
        ->occurrence($containerWidget['occurrence'])
        ->count(3)
        ->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->call('selectAllAssets', containerKey: $containerKey, widgetIndex: $widgetIndex)
        ->callAction(
            'removeAssets',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
            ],
        )
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    expect(
        $widget->assets()
            ->whereNull('page_id')
            ->where('container', $containerKey)
            ->where('occurrence', $containerWidget['occurrence'])
            ->exists(),
    )
        ->toBeFalse();
});

test('can remove page assets', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->widget($widget)
        ->occurrence($containerWidget['occurrence'])
        ->create();

    WidgetAsset::factory()
        ->widget($widget)
        ->page($page, $containerKey, $containerWidget['occurrence'])
        ->count(3)
        ->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $page->id,
    ])
        ->assertSuccessful()
        ->call('selectAllAssets', containerKey: $containerKey, widgetIndex: $widgetIndex)
        ->callAction(
            'removeAssets',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
            ],
        )
        ->assertHasNoFormErrors()
        ->call('saveLayout');

    assertDatabaseMissing('widget_assets', [
        'widget_id' => $widget->id,
        'page_id' => $page->id,
        'container' => $containerKey,
        'occurrence' => $containerWidget['occurrence'],
    ]);

    assertDatabaseHas('widget_assets', [
        'widget_id' => $widget->id,
        'page_id' => null,
        'container' => null,
        'occurrence' => $containerWidget['occurrence'],
    ]);
});

test('can not remove assets if no records selected', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->widget($widget)
        ->occurrence($containerWidget['occurrence'])
        ->count(3)
        ->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->mountAction(
            'removeAssets',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
            ],
        )
        ->assertHasNoFormErrors()
        ->assertActionHalted('removeAssets')
        ->call('saveLayout');

    expect(
        $widget->assets()
            ->whereNull('page_id')
            ->where('container', $containerKey)
            ->where('occurrence', $containerWidget['occurrence'])
            ->count(),
    )
        ->toBe(3);
})->todo();

test('Can revert page assets', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->widget($widget)
        ->page($page, $containerKey, $containerWidget['occurrence'])
        ->count(3)
        ->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $page->id,
    ])
        ->assertSuccessful()
        ->mountAction(
            TestAction::make('togglePageAssets')->arguments([
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
            ]),
        )
        ->callMountedAction();
});
