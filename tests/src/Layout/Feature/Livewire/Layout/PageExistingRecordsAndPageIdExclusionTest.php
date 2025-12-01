<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Livewire\Assets\Table\PageAssetsTable;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('excludes existing page records and current pageId from selection list', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $currentPage = Page::factory()->layout($layout)->create();
    $pages = Page::factory()->count(5)->create();

    $existingRecords = $pages->take(2);
    $visibleExpected = $pages->slice(2)->filter(fn ($page): bool => $page->id !== $currentPage->id)->values();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => true,
        'pageId' => $currentPage->id,
        'siteId' => $currentPage->site_id,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(PageAssetsTable::class, [
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
