<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\Mutations\ReorderLayoutElementAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;

it('reorders a element and keeps related state attached', function (): void {
    $state = new LayoutBuilderStateData(
        containers: ['main' => ['elements' => [
            ['element_key' => 'first'],
            ['element_key' => 'second'],
        ], 'meta' => []]],
        assets: ['main' => [[['asset' => 'first']], [['asset' => 'second']]]],
        originalAssets: ['main' => [[['original' => 'first']], [['original' => 'second']]]],
        selectedRecords: ['main' => [['first-record'], ['second-record']]],
    );

    $result = ReorderLayoutElementAction::run($state, 'main', 'main', 1, 0);

    expect(array_column($result->state->containers['main']['elements'], 'element_key'))->toBe(['second', 'first'])
        ->and($result->state->assets['main'][0][0]['asset'])->toBe('second')
        ->and($result->state->originalAssets['main'][0][0]['original'])->toBe('second')
        ->and($result->state->selectedRecords['main'][0])->toBe(['second-record']);
});

it('moves a element between containers and preserves empty asset slots', function (): void {
    $state = new LayoutBuilderStateData(
        containers: [
            'main' => ['elements' => [['element_key' => 'first'], ['element_key' => 'second']], 'meta' => []],
            'sidebar' => ['elements' => [['element_key' => 'third']], 'meta' => []],
        ],
        assets: ['main' => [[['asset' => 'first']]], 'sidebar' => [[['asset' => 'third']]]],
        originalAssets: ['main' => [], 'sidebar' => []],
        selectedRecords: ['main' => [], 'sidebar' => []],
    );

    $result = ReorderLayoutElementAction::run($state, 'main', 'sidebar', 1, 1);

    expect(array_column($result->state->containers['main']['elements'], 'element_key'))->toBe(['first'])
        ->and(array_column($result->state->containers['sidebar']['elements'], 'element_key'))->toBe(['third', 'second'])
        ->and($result->state->assets['sidebar'][1])->toBe([]);
});
