<?php

declare(strict_types=1);

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Livewire\LayoutBuilder;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

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
    $layout = (new LayoutFactory())->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $widgetKey = $layout->containers[$containerKey]['widgets'][$widgetIndex]['widget_key'];

    $widget = Widget::firstWhere('key', $widgetKey);
    WidgetAsset::factory()->count(2)->create();
    Media::factory()->count(2)->create();
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
    $layout = (new LayoutFactory())->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $occurrence = $layout->containers[$containerKey]['widgets'][$widgetIndex]['occurrence'];
    $widget = Widget::firstWhere('key', $layout->containers[$containerKey]['widgets'][$widgetIndex]['widget_key']);

    $secondWidgetIndex = array_key_last($layout->containers[$containerKey]['widgets']);
    $secondWidget = Widget::firstWhere('key', $layout->containers[$containerKey]['widgets'][$secondWidgetIndex]['widget_key']);
    $secondOccurrence = $layout->containers[$containerKey]['widgets'][$secondWidgetIndex]['occurrence'];

    // 2 existing
    WidgetAsset::factory()
        ->count(2)
        ->widget($widget)
        ->page($page, $containerKey, $occurrence)
        ->create();

    // 5 to add
    $media = Media::factory()->count(2)->create();
    $pages = Page::factory()->count(3)->create();

    // Excluded
    WidgetAsset::factory()
        ->count(3)
        ->create();

    expect($widget->pageAssets($page, $containerKey, $occurrence)->count())
        ->toBe(2)
        ->and($widget->key)
        ->toBe($secondWidget->key);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $page->id,
    ])
        ->assertSuccessful()
        ->call(
            'syncSelectedAssets',
            containerKey: $containerKey,
            widgetIndex: $widgetIndex,
            type: AssetEnum::Media->name,
            hasPageAssets: true,
            assets: $media->map(fn (Media $record): string => (string) $record->uuid)->toArray()
        )
        ->call(
            'syncSelectedAssets',
            containerKey: $containerKey,
            widgetIndex: $widgetIndex,
            type: AssetEnum::Page->name,
            hasPageAssets: true,
            assets: $pages->map(fn (Page $record): string => (string) $record->uuid)->toArray()
        )
        ->call('saveLayout');

    expect($widget->widgetAssets()->count())
        ->toBe(0)
        ->and($widget->pageAssets($page, $containerKey, $occurrence)->count())
        ->toBe(7)
        ->and($secondWidget->widgetAssets()->count())
        ->toBe(0)
        ->and($secondWidget->pageAssets($page, $containerKey, $secondOccurrence)->count())
        ->toBe(0);
});

test('Can sync new widget assets to layout', function (): void {
    $layout = (new LayoutFactory())->containers()->create();

    // 5 to add
    $media = Media::factory()->count(2)->create();
    $pages = Page::factory()->count(3)->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = 2;
    $widget = Widget::firstWhere('key', 'first');

    // 2 existing
    WidgetAsset::factory()
        ->count(2)
        ->widget($widget)
        ->create();

    // 3 excluded
    WidgetAsset::factory()
        ->count(3)
        ->create();

    expect($widget->widgetAssets()->count())
        ->toBe(2);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->call(
            'syncSelectedAssets',
            containerKey: $containerKey,
            widgetIndex: $widgetIndex,
            type: 'media',
            hasPageAssets: false,
            assets: $media->map(fn (Media $record): string => (string) $record->uuid)->toArray()
        )
        ->call(
            'syncSelectedAssets',
            containerKey: $containerKey,
            widgetIndex: $widgetIndex,
            type: 'page',
            hasPageAssets: false,
            assets: $pages->map(fn (Page $record): string => (string) $record->uuid)->toArray()
        )
        ->call('saveLayout');

    expect($widget->widgetAssets()->count())
        ->toBe(7);
});

test('Can sync new page assets', function (): void {
    $layout = (new LayoutFactory())->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $widgetKey = $layout->containers[$containerKey]['widgets'][$widgetIndex]['widget_key'];
    $occurrence = $layout->containers[$containerKey]['widgets'][$widgetIndex]['occurrence'];

    $widget = Widget::firstWhere('key', $widgetKey);

    $media = Media::factory()->count(2)->create();
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
            'syncSelectedAssets',
            containerKey: $containerKey,
            widgetIndex: $widgetIndex,
            type: 'media',
            hasPageAssets: true,
            assets: $media->map(fn (Media $record): string => (string) $record->uuid)->toArray()
        )
        ->call(
            'syncSelectedAssets',
            containerKey: $containerKey,
            widgetIndex: $widgetIndex,
            type: 'page',
            hasPageAssets: true,
            assets: $pages->map(fn (Page $record): string => (string) $record->uuid)->toArray()
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

    $layout = (new LayoutFactory())->state([
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
        ->asset(AssetEnum::Media)
        ->state([
            'container' => 'test',
            'order' => 2,
            'occurrence' => 2,
        ])
        ->create();

    $firstAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset(AssetEnum::Page)
        ->state([
            'container' => 'test',
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
            newIndex: 0
        )
        ->call('saveLayout');

    expect($secondAsset->refresh())
        ->order->toBe(1);

    expect($firstAsset->refresh())
        ->order->toBe(2);
});

test('Can select all widget assets', function (): void {
    $layout = (new LayoutFactory())->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

    foreach ([AssetEnum::Media, AssetEnum::Page, Capell\Layout\Enums\AssetEnum::Content] as $assetType) {
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
            fn (WidgetAsset $layoutAsset): string => $layoutAsset->asset_key
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

test('can add media asset', function (): void {
    $layout = (new LayoutFactory())->containers()->create();
    $media = Media::factory()->make();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->assertActionExists('addAsset')
        ->mountAction(
            'addAsset',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'type' => 'media',
            ]
        )
        ->setActionData([
            'asset' => [
                'file' => UploadedFile::fake()->image('test.jpg'),
                'alt' => $media->name,
                'originalFilename' => 'test.jpg',
            ],
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->call('saveLayout');

    assertDatabaseHas('media', [
        'title' => 'test',
    ]);

    assertDatabaseHas('widget_assets', [
        'page_id' => null,
        'widget_id' => $widget->id,
        'container' => null,
        'occurrence' => null,
        'asset_type' => 'media',
    ]);
});

test('can add page asset', function (): void {
    $layout = (new LayoutFactory())->containers()->create();
    $newData = Page::factory()->make();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->assertActionExists('addAsset')
        ->mountAction(
            'addAsset',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'type' => 'page',
            ]
        )
        ->setActionData([
            'asset' => [
                'layout_id' => $newData->layout_id,
                'site_id' => $newData->site_id,
                'name' => $newData->name,
            ],
        ])
        ->set('mountedActionsData.0.asset.translations', [
            0 => [
                'title' => $newData->name,
                'slug' => Str::slug($newData->name),
                'language_id' => $newData->site->language_id,
            ],
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->call('saveLayout');

    assertDatabaseHas('pages', [
        'name' => $newData->name,
    ]);

    assertDatabaseHas('widget_assets', [
        'page_id' => null,
        'widget_id' => $widget->id,
        'container' => null,
        'occurrence' => null,
        'asset_type' => 'page',
    ]);
});

test('can add media asset to existing widget with page layout', function (): void {
    $layout = (new LayoutFactory())->containers()->create();
    $pageLayout = Page::factory()->layout($layout)->create();

    $media = Media::factory()->make();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->widget($widget)
        ->page($pageLayout, $containerKey, $containerWidget['occurrence'])
        ->asset(AssetEnum::Media)
        ->create();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $pageLayout->id,
    ])
        ->assertSuccessful()
        ->callAction(
            'addAsset',
            data: [
                'asset' => [
                    'file' => UploadedFile::fake()->image('test.jpg'),
                    'alt' => $media->name,
                    'originalFilename' => 'test.jpg',
                ],
            ],
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'type' => 'media',
            ]
        )
        ->assertHasNoActionErrors()
        ->call('saveLayout');

    assertDatabaseHas('media', [
        'alt' => $media->name,
    ]);

    $media = Media::firstWhere('alt', $media->name);

    assertDatabaseHas('widget_assets', [
        'page_id' => $pageLayout->id,
        'widget_id' => $widget->id,
        'container' => $containerKey,
        'occurrence' => $containerWidget['occurrence'],
        'asset_type' => 'media',
        'asset_id' => $media->uuid,
    ]);
});

test('can add media asset to widget with page layout', function (): void {
    $layout = (new LayoutFactory())->containers()->create();
    $pageLayout = Page::factory()->layout($layout)->create();

    $media = Media::factory()->make();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
        'page_id' => $pageLayout->id,
    ])
        ->assertSuccessful()
        ->callAction(
            'addAsset',
            data: [
                'asset' => [
                    'file' => UploadedFile::fake()->image('test.jpg'),
                    'alt' => $media->name,
                    'originalFilename' => 'test.jpg',
                ],
            ],
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'type' => 'media',
            ]
        )
        ->assertHasNoActionErrors()
        ->call('saveLayout');

    assertDatabaseHas('media', [
        'alt' => $media->name,
    ]);

    $media = Media::firstWhere('alt', $media->name);

    assertDatabaseHas('widget_assets', [
        'page_id' => $pageLayout->id,
        'widget_id' => $widget->id,
        'container' => $containerKey,
        'occurrence' => $containerWidget['occurrence'],
        'asset_type' => 'media',
        'asset_id' => $media->uuid,
    ]);
});

test('can add page asset to existing widget with page layout', function (): void {
    $layout = (new LayoutFactory())->containers()->create();
    $pageLayout = Page::factory()
        ->layout($layout)
        ->create();

    $newData = Page::factory()->make();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

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
            'addAsset',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'type' => 'page',
            ]
        )
        ->setActionData([
            'asset' => [
                'layout_id' => $newData->layout_id,
                'site_id' => $newData->site_id,
                'name' => $newData->name,
            ],
        ])
        ->set('mountedActionsData.0.asset.translations', [
            0 => [
                'title' => $newData->name,
                'slug' => Str::slug($newData->name),
                'language_id' => $newData->site->language_id,
            ],
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors()
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
    $layout = (new LayoutFactory())->containers()->create();
    $pageLayout = Page::factory()
        ->layout($layout)
        ->create();

    $newData = Page::factory()->make();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

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
            ]
        )
        ->set('mountedActionsData.0.translations', [])
        ->setActionData([
            'asset' => [
                'layout_id' => $newData->layout_id,
                'site_id' => $newData->site_id,
                'name' => $newData->name,
            ],
        ])
        ->set(
            'mountedActionsData.0.translations',
            [
                0 => [
                    'title' => $newData->name,
                    'slug' => Str::slug($newData->name),
                    'language_id' => $newData->site->language_id,
                ],
            ]
        )
        ->callMountedAction()
        ->assertHasNoActionErrors()
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
    $layout = (new LayoutFactory())->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    livewire(LayoutBuilder::class, ['layout_id' => $layout->id])
        ->assertSuccessful()
        ->mountAction(
            'selectAsset',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'type' => $assetType,
            ]
        )
        ->assertHasNoActionErrors();
})->with(['page', 'media', 'content']);

test('can edit asset', function (): void {
    $layout = (new LayoutFactory())->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

    $layoutAsset = WidgetAsset::factory()
        ->widget($widget)
        ->container($containerKey)
        ->asset(AssetEnum::Page)
        ->create();

    $page = $layoutAsset->asset()->with('translation')->first();

    livewire(LayoutBuilder::class, [
        'layout_id' => $layout->id,
    ])
        ->assertSuccessful()
        ->callAction(
            'editWidgetAsset',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
                'index' => 0,
                'assetType' => $layoutAsset['asset_type'],
            ],
            data: [
                'asset' => [
                    'translations' => [
                        'record-'.$page->translation->id => [
                            'title' => 'testing',
                        ],
                    ],
                ],
            ],
        );

    expect($layoutAsset->refresh())
        ->asset->translation->title->toBe('testing');
});

test('can remove widget assets', function (): void {
    $layout = (new LayoutFactory())->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->widget($widget)
        ->container($containerKey)
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
            ]
        )
        ->assertHasNoActionErrors()
        ->call('saveLayout');

    expect(
        $widget->assets()
            ->whereNull('page_id')
            ->where('container', $containerKey)
            ->where('occurrence', $containerWidget['occurrence'])
            ->exists()
    )
        ->toBeFalse();
});

test('can remove page assets', function (): void {
    $layout = (new LayoutFactory())->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

    WidgetAsset::factory()
        ->widget($widget)
        ->container($containerKey)
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
            ]
        )
        ->assertHasNoActionErrors()
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
        'container' => $containerKey,
        'occurrence' => $containerWidget['occurrence'],
    ]);
});

test('can not remove assets if no records selected', function (): void {
    $layout = (new LayoutFactory())->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);
    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

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
            ]
        )
        ->assertHasNoActionErrors()
        ->assertActionHalted('removeAssets')
        ->call('saveLayout');

    expect(
        $widget->assets()
            ->whereNull('page_id')
            ->where('container', $containerKey)
            ->where('occurrence', $containerWidget['occurrence'])
            ->count()
    )
        ->toBe(3);
})->todo();

test('Can revert page assets', function (): void {
    $layout = (new LayoutFactory())->containers()->create();
    $page = Page::factory()->layout($layout)->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

    $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

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
        ->callAction(
            'convertPageAssets',
            arguments: [
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
            ]
        );
});

todo('add tests for editWidgetAsset');
