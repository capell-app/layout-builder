<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Livewire\Assets\Table\ContentAssetsTable;
use Capell\Layout\Livewire\Assets\Table\PageAssetsTable;
use Capell\Layout\Models\Content;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

$types = ['content', 'page'];

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('filters by site for contents assets', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $otherSiteContent = Content::factory()->create();
    $site = Site::factory()->create();
    $siteContents = Content::factory()->site($site)->count(4)->create();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(ContentAssetsTable::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($siteContents)
        ->filterTable('site_id', $site->id)
        ->assertCountTableRecords(4)
        ->assertCanNotSeeTableRecords([$otherSiteContent]);
});

it('filters by site for page assets', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $otherSitePage = Page::factory()->create();
    $site = Site::factory()->create();
    $sitePages = Page::factory()->count(4)->site($site)->create();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(PageAssetsTable::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($sitePages)
        ->filterTable('site_id', $site->id)
        ->assertCanNotSeeTableRecords([$otherSitePage]);
});

it('dispatches sync-selected-assets event with selected records for each asset type', function (string $assetType): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $site = Site::factory()->create();
    $records = match ($assetType) {
        'content' => Content::factory()->recycle($site)->count(3)->create(),
        'page' => Page::factory()->recycle($site)->count(3)->create(),
    };

    $component = match ($assetType) {
        'content' => ContentAssetsTable::class,
        'page' => PageAssetsTable::class,
    };

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
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

it('searches within contents assets table', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $contents = Content::factory()->count(3)->create();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
        'widgetIndex' => $widgetIndex,
    ];

    $first = $contents->first();

    livewire(ContentAssetsTable::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->searchTable((string) $first->id)
        ->assertCanSeeTableRecords([$first]);
});
