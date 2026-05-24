<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\Mutations\ReorderLayoutContainerAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;

it('reorders containers while preserving metadata and state keyed by container', function (): void {
    $state = new LayoutBuilderStateData(
        containers: [
            'main' => ['widgets' => [['widget_key' => 'first']], 'meta' => ['colspan' => 8]],
            'sidebar' => ['widgets' => [], 'meta' => ['colspan' => 4]],
        ],
        assets: ['main' => [[['asset' => 'first']]], 'sidebar' => []],
        originalAssets: ['main' => [[['original' => 'first']]], 'sidebar' => []],
        selectedRecords: ['main' => [['record']], 'sidebar' => []],
    );

    $result = ReorderLayoutContainerAction::run($state, 'main', 1);

    expect(array_keys($result->state->containers))->toBe(['sidebar', 'main'])
        ->and($result->state->containers['main']['meta']['colspan'])->toBe(8)
        ->and($result->state->assets['main'][0][0]['asset'])->toBe('first');
});
