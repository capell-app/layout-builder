<?php

declare(strict_types=1);

use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Livewire\Assets\Table\ContentAssetsTable;
use Capell\Layout\Models\Content;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)->group('pages');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('excludes existing content records from selection list', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $containerKey = array_key_first($layout->containers);
    $widgetIndex = array_key_first($layout->containers[$containerKey]['widgets']);

    $allContents = Content::factory()->count(5)->create();
    $excluded = $allContents->take(2);
    $expectedVisible = $allContents->slice(2);

    $arguments = [
        'containerKey' => $containerKey,
        'hasPageAssets' => false,
        'widgetIndex' => $widgetIndex,
    ];

    livewire(ContentAssetsTable::class, [
        'actionModalId' => 'select-assets',
        'arguments' => $arguments,
        'existingRecords' => $excluded->pluck('id')->toArray(),
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords($expectedVisible)
        ->assertCanNotSeeTableRecords($excluded);
});
