<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Support\LayoutMutationHistory;

function historyState(string $elementKey): LayoutBuilderStateData
{
    return new LayoutBuilderStateData(
        containers: ['main' => ['elements' => [['element_key' => $elementKey]], 'meta' => []]],
        assets: ['main' => [[]]],
        originalAssets: ['main' => [[]]],
        selectedRecords: ['main' => [[]]],
    );
}

it('undoes and redoes layout state snapshots', function (): void {
    $history = new LayoutMutationHistory(historyState('first'));
    $history->push(historyState('second'));
    $history->push(historyState('third'));

    expect($history->canUndo())->toBeTrue()
        ->and($history->undo()->containers['main']['elements'][0]['element_key'])->toBe('second')
        ->and($history->undo()->containers['main']['elements'][0]['element_key'])->toBe('first')
        ->and($history->canUndo())->toBeFalse()
        ->and($history->redo()->containers['main']['elements'][0]['element_key'])->toBe('second');
});

it('clears redo history when a new mutation branches from an undone state', function (): void {
    $history = new LayoutMutationHistory(historyState('first'));
    $history->push(historyState('second'));
    $history->undo();
    $history->push(historyState('branch'));

    expect($history->canRedo())->toBeFalse();
});
