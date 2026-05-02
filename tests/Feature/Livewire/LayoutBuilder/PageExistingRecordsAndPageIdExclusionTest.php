<?php

declare(strict_types=1);

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Page;
use Capell\Mosaic\Database\Factories\LayoutFactory;
use Capell\Mosaic\Livewire\Assets\Table\PageAssets;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('excludes only the current pageId when no existing records are provided', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $currentPage = Page::factory()->layout($layout)->create();
    $otherPages = Page::factory()->count(3)->create();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => true,
        'pageId' => $currentPage->id,
        'siteId' => $currentPage->site_id,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(PageAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords($otherPages)
        ->assertCanNotSeeTableRecords([$currentPage]);
});

it('excludes existing page records and current pageId from selection list', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $currentPage = Page::factory()->layout($layout)->create();
    $pages = Page::factory()->count(5)->create();

    $existingRecords = $pages->take(2);
    $visibleExpected = $pages->slice(2)->filter(fn (Pageable $page): bool => $page->id !== $currentPage->id)->values();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => true,
        'pageId' => $currentPage->id,
        'siteId' => $currentPage->site_id,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(PageAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
        'existingRecords' => $existingRecords->pluck('id')->toArray(),
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->assertCountTableRecords($visibleExpected->count())
        ->assertCanSeeTableRecords($visibleExpected)
        ->assertCanNotSeeTableRecords($existingRecords)
        ->assertCanNotSeeTableRecords([$currentPage]);
});
