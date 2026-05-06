<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Livewire\Assets\Table\PageAssets;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('renders page assets table', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $page = Page::factory()->layout($layout)->create();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
        'pageId' => $page->id,
        'siteId' => $page->site_id,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(PageAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
        'type' => 'page',
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments);
});

it('renders page assets table with existing records', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $page = Page::factory()->layout($layout)->create();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
        'pageId' => $page->id,
        'siteId' => $page->site_id,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(PageAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
        'existingRecords' => [999, 998],
        'type' => 'page',
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->assertSet('existingRecords', [999, 998]);
});

it('renders page assets table without page context', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(PageAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
        'type' => 'page',
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments);
});
