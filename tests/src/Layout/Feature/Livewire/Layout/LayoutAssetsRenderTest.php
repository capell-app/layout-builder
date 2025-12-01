<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Livewire\Assets\Table\ContentAssetsTable;
use Capell\Layout\Livewire\Assets\Table\PageAssetsTable;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

$types = ['content', 'page'];

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('renders assets tables for each asset type', function (string $assetType): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $page = Page::factory()->layout($layout)->create();

    $component = match ($assetType) {
        'content' => ContentAssetsTable::class,
        'page' => PageAssetsTable::class,
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
        'tableArguments' => $arguments,
        'type' => $assetType,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments);
})->with($types);
