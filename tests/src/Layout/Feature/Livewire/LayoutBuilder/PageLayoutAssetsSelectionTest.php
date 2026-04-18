<?php

declare(strict_types=1);

use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Page;
use Capell\Mosaic\Database\Factories\LayoutFactory;
use Capell\Mosaic\Livewire\Assets\Table\ContentAssets;
use Capell\Mosaic\Livewire\Assets\Table\PageAssets;
use Capell\Mosaic\Models\Section;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Database\Eloquent\Model;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

$types = ['content', 'page'];

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('excludes existing page assets when selecting new ones', function (): void {
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

    $newPages = Page::factory()->count(3)->create();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => true,
        'pageId' => $page->id,
        'siteId' => $page->site_id,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(PageAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
        'existingRecords' => $existingAssets->pluck('asset_id')->toArray(),
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->assertCountTableRecords(3) // only new pages should be listed
        ->assertCanSeeTableRecords($newPages)
        ->assertCanNotSeeTableRecords($existingAssets->map(fn (WidgetAsset $asset): Model => $asset->asset)->all());
});

it('excludes existing content assets when selecting new ones in page context', function (): void {
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
        ->asset(Capell\Mosaic\Enums\AssetEnum::Section)
        ->create();

    $newContents = Section::factory()->count(3)->create();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => true,
        'pageId' => $page->id,
        'siteId' => $page->site_id,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(ContentAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
        'existingRecords' => $existingAssets->pluck('asset_id')->toArray(),
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords($newContents)
        ->assertCanNotSeeTableRecords($existingAssets->map(fn (WidgetAsset $asset): Model => $asset->asset)->all());
});

it('dispatches sync-selected-assets for page layout context', function (string $assetType): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $page = Page::factory()->layout($layout)->create();

    $records = match ($assetType) {
        'content' => Section::factory()->count(3)->create(),
        'page' => Page::factory()->count(3)->create(),
    };

    $component = match ($assetType) {
        'content' => ContentAssets::class,
        'page' => PageAssets::class,
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
        'tableArguments' => $arguments,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->assertCountTableRecords(3)
        ->selectTableRecords($records->pluck('id')->toArray())
        // ->callAction('selectRecords')
        ->call('selectRecords')
        ->assertDispatched(
            'sync-selected-assets',
            arguments: $arguments,
            type: $assetType,
            assets: $records->pluck('id')->toArray(),
        )
        ->assertDispatched('close-modal', id: 'select-assets');
})->with($types);
