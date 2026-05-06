<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Livewire\Assets\Table\PageAssets;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

beforeEach(function (): void {
    test()->actingAsAdmin();
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

    livewire(PageAssets::class, [
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

it('dispatches sync-selected-assets event with selected page records', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $site = Site::factory()->create();
    $records = Page::factory()->recycle($site)->count(3)->create();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(PageAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->assertCountTableRecords(3)
        ->selectTableRecords($records->pluck('id')->toArray())
        ->callAction('selectRecords')
        ->assertDispatched(
            'sync-selected-assets',
            arguments: $arguments,
            type: 'page',
            assets: $records->pluck('id')->toArray(),
        )
        ->assertDispatched('close-modal', id: 'select-assets');
});

it('searches within page assets table', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $pages = Page::factory()->count(3)->create();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
        'widgetIndex' => $widgetIndex,
    ];

    $first = $pages->first();

    livewire(PageAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->searchTable((string) $first->id)
        ->assertCanSeeTableRecords([$first]);
});
