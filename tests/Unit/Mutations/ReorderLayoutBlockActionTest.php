<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\Mutations\ReorderLayoutBlockAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;

it('reorders a block and keeps related state attached', function (): void {
    $state = new LayoutBuilderStateData(
        containers: ['main' => ['widgets' => [
            ['widget_key' => 'first'],
            ['widget_key' => 'second'],
        ], 'meta' => []]],
        assets: ['main' => [[['asset' => 'first']], [['asset' => 'second']]]],
        originalAssets: ['main' => [[['original' => 'first']], [['original' => 'second']]]],
        selectedRecords: ['main' => [['first-record'], ['second-record']]],
    );

    $result = ReorderLayoutBlockAction::run($state, 'main', 'main', 1, 0);

    expect(array_column($result->state->containers['main']['widgets'], 'widget_key'))->toBe(['second', 'first'])
        ->and($result->state->assets['main'][0][0]['asset'])->toBe('second')
        ->and($result->state->originalAssets['main'][0][0]['original'])->toBe('second')
        ->and($result->state->selectedRecords['main'][0])->toBe(['second-record']);
});

it('moves a block between containers and preserves empty asset slots', function (): void {
    $state = new LayoutBuilderStateData(
        containers: [
            'main' => ['widgets' => [['widget_key' => 'first'], ['widget_key' => 'second']], 'meta' => []],
            'sidebar' => ['widgets' => [['widget_key' => 'third']], 'meta' => []],
        ],
        assets: ['main' => [[['asset' => 'first']]], 'sidebar' => [[['asset' => 'third']]]],
        originalAssets: ['main' => [], 'sidebar' => []],
        selectedRecords: ['main' => [], 'sidebar' => []],
    );

    $result = ReorderLayoutBlockAction::run($state, 'main', 'sidebar', 1, 1);

    expect(array_column($result->state->containers['main']['widgets'], 'widget_key'))->toBe(['first'])
        ->and(array_column($result->state->containers['sidebar']['widgets'], 'widget_key'))->toBe(['third', 'second'])
        ->and($result->state->assets['sidebar'][1])->toBe([]);
});
