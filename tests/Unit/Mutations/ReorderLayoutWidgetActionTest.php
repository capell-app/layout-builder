<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\Mutations\ReorderLayoutWidgetAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;

it('reorders a widget and keeps related state attached', function (): void {
    $state = new LayoutBuilderStateData(
        containers: ['main' => ['widgets' => [
            ['widget_key' => 'first'],
            ['widget_key' => 'second'],
        ], 'meta' => []]],
        assets: ['main' => [[['asset' => 'first']], [['asset' => 'second']]]],
        originalAssets: ['main' => [[['original' => 'first']], [['original' => 'second']]]],
        selectedRecords: ['main' => [['first-record'], ['second-record']]],
    );

    $result = ReorderLayoutWidgetAction::run($state, 'main', 'main', 1, 0);

    expect(array_column($result->state->widgets('main'), 'widget_key'))->toBe(['second', 'first'])
        ->and($result->state->assetSlot('main', 0)[0]['asset'])->toBe('second')
        ->and($result->state->originalAssetSlot('main', 0)[0]['original'])->toBe('second')
        ->and($result->state->selectedRecordSlot('main', 0))->toBe(['second-record']);
});

it('moves a widget between containers and preserves empty asset slots', function (): void {
    $state = new LayoutBuilderStateData(
        containers: [
            'main' => ['widgets' => [['widget_key' => 'first'], ['widget_key' => 'second']], 'meta' => []],
            'sidebar' => ['widgets' => [['widget_key' => 'third']], 'meta' => []],
        ],
        assets: ['main' => [[['asset' => 'first']]], 'sidebar' => [[['asset' => 'third']]]],
        originalAssets: ['main' => [], 'sidebar' => []],
        selectedRecords: ['main' => [], 'sidebar' => []],
    );

    $result = ReorderLayoutWidgetAction::run($state, 'main', 'sidebar', 1, 1);

    expect(array_column($result->state->widgets('main'), 'widget_key'))->toBe(['first'])
        ->and(array_column($result->state->widgets('sidebar'), 'widget_key'))->toBe(['third', 'second'])
        ->and($result->state->assetSlot('sidebar', 1))->toBe([]);
});
