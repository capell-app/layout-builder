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

it('searches within page assets table in page layout context', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $page = Page::factory()->layout($layout)->create();
    $pages = Page::factory()->count(4)->create();

    $first = $pages->first();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => true,
        'pageId' => $page->id,
        'siteId' => $page->site_id,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(PageAssetsTable::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->searchTable((string) $first->id)
        ->assertCanSeeTableRecords([$first]);
});
