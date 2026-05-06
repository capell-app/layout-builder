<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Livewire\Assets\Table\PageAssets;
use Capell\LayoutBuilder\Livewire\Assets\Table\SectionAssets;
use Capell\LayoutBuilder\Models\Section;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

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

    livewire(PageAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->searchTable((string) $first->id)
        ->assertCanSeeTableRecords([$first]);
});

it('searches within section assets table in page layout context', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $page = Page::factory()->layout($layout)->create();
    $sections = Section::factory()->count(4)->create();

    $first = $sections->first();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => true,
        'pageId' => $page->id,
        'siteId' => $page->site_id,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(SectionAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->searchTable((string) $first->id)
        ->assertCanSeeTableRecords([$first]);
});
