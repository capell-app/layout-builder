<?php

declare(strict_types=1);

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Livewire\Assets\Table\ContentsTable;
use Capell\Layout\Livewire\Assets\Table\PagesTable;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

$types = ['content', 'page'];

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('can render assets table', function (string $assetType): void {
    $layout = (new LayoutFactory)->containers()->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $page = Page::factory()->layout($layout)->create();

    $component = match ($assetType) {
        'content' => ContentsTable::class,
        'page' => PagesTable::class,
    };

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
        'pageId' => $page->id,
        'siteId' => $page->site_id,
        'widgetIndex' => $widgetIndex,
    ];

    livewire($component, [
        'actionModalId' => 'select-assets',
        'arguments' => $arguments,
        'type' => $assetType,
    ])
        ->assertSuccessful();
})->with($types);

describe('layout', function () use ($types): void {
    test('contents assets table can select assets', function (): void {
        $layout = (new LayoutFactory)->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $otherSiteContents = Content::factory()->create();
        $site = Site::factory()->create();
        $contents = Content::factory()->site($site)->count(4)->create();

        $arguments = [
            'containerKey' => $containerKey,
            'hasPageAssets' => false,
            'widgetIndex' => $widgetIndex,
        ];

        livewire(ContentsTable::class, [
            'actionModalId' => 'select-assets',
            'arguments' => $arguments,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(5)
            ->assertCanSeeTableRecords($contents)
            ->filterTable('site_id', $site->id)
            ->assertCountTableRecords(4)
            ->assertCanNotSeeTableRecords([$otherSiteContents]);
    });

    test('page assets table can select assets', function (): void {
        $layout = (new LayoutFactory)->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $otherSitePages = Page::factory()->create();
        $site = Site::factory()->create();
        $pages = Page::factory()->count(4)->site($site)->create();

        $arguments = [
            'containerKey' => $containerKey,
            'hasPageAssets' => false,
            'widgetIndex' => $widgetIndex,
        ];

        livewire(PagesTable::class, [
            'actionModalId' => 'select-assets',
            'arguments' => $arguments,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(5)
            ->assertCanSeeTableRecords($pages)
            ->filterTable('site_id', $site->id)
            ->assertCanNotSeeTableRecords([$otherSitePages]);
    });

    test('sync selected assets to layout', function (string $assetType): void {
        $layout = (new LayoutFactory)->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $site = Site::factory()->create();
        $records = match ($assetType) {
            'content' => Content::factory()->recycle($site)->count(4)->create(),
            'page' => Page::factory()->recycle($site)->count(4)->create(),
        };

        $component = match ($assetType) {
            'content' => ContentsTable::class,
            'page' => PagesTable::class,
        };

        $arguments = [
            'containerKey' => $containerKey,
            'hasPageAssets' => false,
            'widgetIndex' => $widgetIndex,
        ];

        livewire($component, [
            'actionModalId' => 'select-assets',
            'arguments' => $arguments,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(4)
            ->selectTableRecords($records->pluck('id')->toArray())
            ->callAction(TestAction::make('selectRecords')->table()->bulk())
            ->assertDispatched('sync-selected-assets')
            ->assertDispatched(
                'sync-selected-assets',
                arguments: $arguments,
                type: $assetType,
                assets: $records->pluck('id')->toArray(),
            )
            ->assertDispatched('close-modal', id: 'select-assets');
    })->with($types);

    test('sync selected media assets to layout', function (string $assetType): void {
        $layout = (new LayoutFactory)->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $site = Site::factory()->create();
        $records = match ($assetType) {
            'content' => Content::factory()->recycle($site)->count(4)->create(),
            'page' => Page::factory()->recycle($site)->count(4)->create(),
        };

        $component = match ($assetType) {
            'content' => ContentsTable::class,
            'page' => PagesTable::class,
        };

        $arguments = [
            'containerKey' => $containerKey,
            'hasPageAssets' => false,
            'widgetIndex' => $widgetIndex,
        ];

        livewire($component, [
            'actionModalId' => 'select-assets',
            'arguments' => $arguments,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(4)
            ->assertCanSeeTableRecords($records)
            ->selectTableRecords($records->pluck('id')->toArray())
            ->callAction(TestAction::make('selectRecords')->table()->bulk())
            ->assertDispatched(
                'sync-selected-assets',
                arguments: $arguments,
                type: $assetType,
                assets: $records->pluck('id')->toArray(),
            )
            ->assertDispatched('close-modal', id: 'select-assets');
    })->with($types);
});

describe('page layout', function () use ($types): void {
    test('page assets table can be selected', function (): void {
        $layout = (new LayoutFactory)->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $page = Page::factory()->layout($layout)->create();

        $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

        $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

        $existingAssets = WidgetAsset::factory()
            ->count(2)
            ->widget($widget)
            ->page($page, $containerKey, $containerWidget['occurrence'])
            ->asset(AssetEnum::Page)
            ->create();

        $pages = Page::factory()->count(4)->create();
        $otherSitePage = Page::factory()->create();

        $arguments = [
            'containerKey' => $containerKey,
            'hasPageAssets' => true,
            'pageId' => $page->id,
            'siteId' => $page->site_id,
            'widgetIndex' => $widgetIndex,
        ];

        livewire(PagesTable::class, [
            'actionModalId' => 'select-assets',
            'arguments' => $arguments,
            'existingRecords' => $existingAssets->pluck('asset_id')->toArray(),
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(5)
            ->assertCanSeeTableRecords($pages)
            ->filterTable('site_id', $page->site_id)
            ->assertCanNotSeeTableRecords([$otherSitePage])
            ->removeTableFilters()
            ->searchTable($pages->first()->id)
            ->assertCanSeeTableRecords([$pages->first()]);
    });

    test('sync selected assets to layout', function (string $assetType): void {
        $layout = (new LayoutFactory)->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $page = Page::factory()->layout($layout)->create();

        $records = match ($assetType) {
            'content' => Content::factory()->count(4)->create(),
            'page' => Page::factory()->count(4)->create(),
        };

        $component = match ($assetType) {
            'content' => ContentsTable::class,
            'page' => PagesTable::class,
        };

        $arguments = [
            'containerKey' => $containerKey,
            'hasPageAssets' => true,
            'pageId' => $page->id,
            'siteId' => $page->site_id,
            'widgetIndex' => $widgetIndex,
        ];

        livewire($component, [
            'actionModalId' => 'select-assets',
            'arguments' => $arguments,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(4)
            ->selectTableRecords($records->pluck('id')->toArray())
            ->callAction(TestAction::make('selectRecords')->table()->bulk())
            ->assertDispatched(
                'sync-selected-assets',
                arguments: $arguments,
                type: $assetType,
                assets: $records->pluck('id')->toArray(),
            )
            ->assertDispatched('close-modal', id: 'select-assets');
    })->with($types);
});
