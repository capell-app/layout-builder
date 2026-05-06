<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Livewire\Assets\Table\SectionAssets;
use Capell\LayoutBuilder\Models\Section;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('shows all content records when no existing records are provided', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $allContents = Section::factory()->count(5)->create();

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(SectionAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($allContents);
});

it('excludes existing content records from selection list', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $allContents = Section::factory()->count(5)->create();
    $excluded = $allContents->take(2);
    $expectedVisible = $allContents->slice(2);

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(SectionAssets::class, [
        'actionModalId' => 'select-assets',
        'tableArguments' => $arguments,
        'existingRecords' => $excluded->pluck('id')->toArray(),
    ])
        ->assertSuccessful()
        ->assertSet('tableArguments', $arguments)
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords($expectedVisible)
        ->assertCanNotSeeTableRecords($excluded);
});
