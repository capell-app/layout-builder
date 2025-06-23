<?php

declare(strict_types=1);

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Livewire\Assets\Table\ContentsTable;
use Capell\Layout\Livewire\Assets\Table\MediaTable;
use Capell\Layout\Livewire\Assets\Table\PagesTable;
use Capell\Layout\Livewire\LayoutBuilder;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Database\Eloquent\Model;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

$types = ['content', 'media', 'page'];

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('can render assets table', function (string $assetType): void {
    $layout = (new LayoutFactory())->containers()->create();

    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $page = Page::factory()->layout($layout)->create();

    $component = match ($assetType) {
        'content' => ContentsTable::class,
        'media' => MediaTable::class,
        'page' => PagesTable::class,
    };

    livewire($component, [
        'actionId' => 'select-assets',
        'containerKey' => $containerKey,
        'pageId' => $page->id,
        'siteId' => $page->site_id,
        'type' => $assetType,
        'widgetIndex' => $widgetIndex,
    ])
        ->assertSuccessful();
})->with($types);

describe('layout', function () use ($types): void {
    test('contents assets table can select assets', function (): void {
        $layout = (new LayoutFactory())->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $otherSiteContents = Content::factory()->create();
        $site = Site::factory()->create();
        $contents = Content::factory()->site($site)->count(4)->create();

        livewire(ContentsTable::class, [
            'actionId' => 'select-assets',
            'containerKey' => $containerKey,
            'widgetIndex' => $widgetIndex,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(5)
            ->assertCanSeeTableRecords($contents)
            ->filterTable('site_id', $site->id)
            ->assertCountTableRecords(4)
            ->assertCanNotSeeTableRecords([$otherSiteContents]);
    });

    test('media assets table can select assets', function (): void {
        $layout = (new LayoutFactory())->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $media = Media::factory()->count(4)->create();

        livewire(MediaTable::class, [
            'actionId' => 'select-assets',
            'containerKey' => $containerKey,
            'widgetIndex' => $widgetIndex,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(4)
            ->assertCanSeeTableRecords($media);
    });

    test('page assets table can select assets', function (): void {
        $layout = (new LayoutFactory())->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $otherSitePages = Page::factory()->create();
        $site = Site::factory()->create();
        $pages = Page::factory()->count(4)->site($site)->create();

        livewire(PagesTable::class, [
            'actionId' => 'select-assets',
            'containerKey' => $containerKey,
            'widgetIndex' => $widgetIndex,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(5)
            ->assertCanSeeTableRecords($pages)
            ->filterTable('filter', ['site_id' => $site->id])
            ->assertCanNotSeeTableRecords([$otherSitePages]);
    });

    test('sync selected assets to layout', function (string $assetType): void {
        $layout = (new LayoutFactory())->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $site = Site::factory()->create();
        $records = match ($assetType) {
            'content' => Content::factory()->recycle($site)->count(4)->create(),
            'media' => Media::factory()->recycle($site)->count(4)->create(),
            'page' => Page::factory()->recycle($site)->count(4)->create(),
        };

        $component = match ($assetType) {
            'content' => ContentsTable::class,
            'media' => MediaTable::class,
            'page' => PagesTable::class,
        };

        livewire($component, [
            'actionId' => 'select-assets',
            'containerKey' => $containerKey,
            'widgetIndex' => $widgetIndex,
            'hasPageAssets' => false,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(4)
            ->callTableBulkAction('selectRecords', $records)
            ->assertDispatchedTo(
                LayoutBuilder::class,
                'sync-selected-assets',
                containerKey: $containerKey,
                widgetIndex: $widgetIndex,
                type: $assetType,
                hasPageAssets: false,
                assets: $records->map(fn (Model $record): string => (string) $record->uuid)->toArray(),
            )
            ->assertDispatched('close-modal', id: 'select-assets');
    })->with($types);

    test('sync selected media assets to layout', function (string $assetType): void {
        $layout = (new LayoutFactory())->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $site = Site::factory()->create();
        $records = match ($assetType) {
            'content' => Content::factory()->recycle($site)->count(4)->create(),
            'media' => Media::factory()->recycle($site)->count(4)->create(),
            'page' => Page::factory()->recycle($site)->count(4)->create(),
        };

        $component = match ($assetType) {
            'content' => ContentsTable::class,
            'media' => MediaTable::class,
            'page' => PagesTable::class,
        };

        livewire($component, [
            'actionId' => 'select-assets',
            'containerKey' => $containerKey,
            'widgetIndex' => $widgetIndex,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(4)
            ->assertCanSeeTableRecords($records)
            ->callTableBulkAction('selectRecords', $records)
            ->assertDispatchedTo(
                LayoutBuilder::class,
                'sync-selected-assets',
                containerKey: $containerKey,
                widgetIndex: $widgetIndex,
                type: $assetType,
                hasPageAssets: false,
                assets: $records->map(fn (Model $record): string => (string) $record->uuid)->toArray(),
            )
            ->assertDispatched('close-modal', id: 'select-assets');
    })->with($types);
});

describe('page layout', function () use ($types): void {
    test('media assets table can be selected', function (): void {
        $layout = (new LayoutFactory())->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $page = Page::factory()->layout($layout)->create();

        $media = Media::factory()->count(4)->create();

        livewire(MediaTable::class, [
            'actionId' => 'select-assets',
            'containerKey' => $containerKey,
            'pageId' => $page->id,
            'siteId' => $page->site_id,
            'widgetIndex' => $widgetIndex,
            'hasPageAssets' => true,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(4)
            ->assertCanSeeTableRecords($media);
    });

    test('page assets table can be selected', function (): void {
        $layout = (new LayoutFactory())->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $page = Page::factory()->layout($layout)->create();

        $containerWidget = $layout->containers[$containerKey]['widgets'][$widgetIndex];

        $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

        $existingAssets = WidgetAsset::factory()
            ->count(2)
            ->widget($widget)
            ->page($page, $containerKey, $containerWidget['occurrence'])
            ->asset(AssetEnum::Page)
            ->create();

        $pages = Page::factory()->count(4)->create();
        $otherSitePage = Page::factory()->create();

        livewire(PagesTable::class, [
            'actionId' => 'select-assets',
            'containerKey' => $containerKey,
            'pageId' => $page->id,
            'siteId' => $page->site_id,
            'widgetIndex' => $widgetIndex,
            'existingRecords' => $existingAssets->pluck('asset_id')->toArray(),
            'hasPageAssets' => true,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(5)
            ->assertCanSeeTableRecords($pages)
            ->filterTable('filter', ['site_id' => $page->site_id])
            ->assertCanNotSeeTableRecords([$otherSitePage])
            ->removeTableFilters()
            ->searchTable($pages->first()->id)
            ->assertCanSeeTableRecords([$pages->first()]);
    });

    test('sync selected assets to layout', function (string $assetType): void {
        $layout = (new LayoutFactory())->containers()->create();
        $containerKey = array_key_first($layout->containers);
        $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

        $page = Page::factory()->layout($layout)->create();

        $records = match ($assetType) {
            'content' => Content::factory()->count(4)->create(),
            'media' => Media::factory()->count(4)->create(),
            'page' => Page::factory()->count(4)->create(),
        };

        $component = match ($assetType) {
            'content' => ContentsTable::class,
            'media' => MediaTable::class,
            'page' => PagesTable::class,
        };

        livewire($component, [
            'actionId' => 'select-assets',
            'containerKey' => $containerKey,
            'widgetIndex' => $widgetIndex,
            'hasPageAssets' => true,
            'pageId' => $page->id,
            'siteId' => $page->site_id,
        ])
            ->assertSuccessful()
            ->assertCountTableRecords(4)
            ->callTableBulkAction('selectRecords', $records)
            ->assertDispatchedTo(
                LayoutBuilder::class,
                'sync-selected-assets',
                containerKey: $containerKey,
                widgetIndex: $widgetIndex,
                type: $assetType,
                hasPageAssets: true,
                assets: $records->map(fn (Model $record): string => (string) $record->uuid)->toArray(),
            )
            ->assertDispatched('close-modal', id: 'select-assets');
    })->with($types);
});
