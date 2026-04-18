<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Mosaic\Database\Factories\LayoutFactory;
use Capell\Mosaic\Livewire\Assets\Table\ContentAssets;
use Capell\Mosaic\Livewire\Assets\Table\PageAssets;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

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
        'content' => ContentAssets::class,
        'page' => PageAssets::class,
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

it('renders assets tables with existing records for each asset type', function (string $assetType): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $page = Page::factory()->layout($layout)->create();

    $component = match ($assetType) {
        'content' => ContentAssets::class,
        'page' => PageAssets::class,
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
        'existingRecords' => [999, 998],
        'type' => $assetType,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->assertSet('existingRecords', [999, 998]);
})->with($types);

it('renders assets tables without page context', function (string $assetType): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $component = match ($assetType) {
        'content' => ContentAssets::class,
        'page' => PageAssets::class,
    };

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
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
