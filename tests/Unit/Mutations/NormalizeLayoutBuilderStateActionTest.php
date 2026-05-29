<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\Mutations\NormalizeLayoutBuilderStateAction;
use Capell\LayoutBuilder\Actions\Mutations\PushLayoutMutationSnapshotAction;
use Capell\LayoutBuilder\Actions\Mutations\RedoLayoutMutationSnapshotAction;
use Capell\LayoutBuilder\Actions\Mutations\UndoLayoutMutationSnapshotAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutChangeData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;

it('represents layout builder state and mutation output with typed data', function (): void {
    $state = new LayoutBuilderStateData(
        containers: ['main' => ['widgets' => [], 'meta' => ['colspan' => 12]]],
        assets: ['main' => []],
        originalAssets: ['main' => []],
        selectedRecords: ['main' => []],
    );

    $diagnostic = new LayoutDiagnosticData(
        severity: LayoutDiagnosticSeverity::Warning,
        code: 'responsive_colspan_clamped',
        message: 'Responsive colspan was clamped.',
        containerKey: 'main',
        blockIndex: null,
    );

    $blockingDiagnostic = new LayoutDiagnosticData(
        severity: LayoutDiagnosticSeverity::Blocking,
        code: 'missing_required_block',
        message: 'A required block is missing.',
        containerKey: 'main',
        blockIndex: null,
    );

    $change = new LayoutChangeData(
        type: 'container_resized',
        label: 'Container main resized',
        containerKey: 'main',
        blockIndex: null,
    );

    $result = new LayoutMutationResultData(
        state: $state,
        diagnostics: [$diagnostic],
        changes: [$change],
    );

    $blockingResult = new LayoutMutationResultData(
        state: $state,
        diagnostics: [$blockingDiagnostic],
        changes: [],
    );

    $stateFromLivewire = LayoutBuilderStateData::fromLivewire(
        containers: null,
        assets: ['main' => []],
        originalAssets: null,
        selectedRecords: ['main' => []],
    );

    expect(LayoutBreakpoint::Desktop->value)->toBe('desktop')
        ->and(LayoutBreakpoint::Tablet->value)->toBe('tablet')
        ->and(LayoutBreakpoint::Mobile->value)->toBe('mobile')
        ->and(LayoutBreakpoint::fromNullable(null))->toBeNull()
        ->and(LayoutBreakpoint::fromNullable('tablet'))->toBe(LayoutBreakpoint::Tablet)
        ->and($result->hasBlockingDiagnostics())->toBeFalse()
        ->and($blockingResult->hasBlockingDiagnostics())->toBeTrue()
        ->and($result->state->containers['main']['meta']['colspan'])->toBe(12)
        ->and($stateFromLivewire->containers)->toBe([])
        ->and($stateFromLivewire->assets)->toBe(['main' => []])
        ->and($stateFromLivewire->originalAssets)->toBe([])
        ->and($stateFromLivewire->selectedRecords)->toBe(['main' => []])
        ->and($state->toLivewirePayload())->toBe([
            'containers' => ['main' => ['widgets' => [], 'meta' => ['colspan' => 12]]],
            'assets' => ['main' => []],
            'originalAssets' => ['main' => []],
            'selectedRecords' => ['main' => []],
        ]);
});

it('normalizes sparse block state and clamps responsive metadata', function (): void {
    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'hero'],
                    ['widget_key' => 'cards'],
                    ['widget_key' => 'cta'],
                ],
                'meta' => [
                    'colspan' => 14,
                    'responsive' => [
                        'desktop' => ['colspan' => 8],
                        'tablet' => ['colspan' => 0],
                        'watch' => ['colspan' => 3],
                    ],
                ],
            ],
        ],
        assets: ['main' => [0 => [['asset_type' => 'page']], 2 => []]],
        originalAssets: ['main' => [0 => [['asset_type' => 'page']]]],
        selectedRecords: ['main' => [2 => ['record']]],
    );

    $result = NormalizeLayoutBuilderStateAction::run($state);

    expect($result->state->containers['main']['meta']['colspan'])->toBe(12)
        ->and($result->state->containers['main']['meta']['responsive'])->toBe([
            'desktop' => ['colspan' => 8],
            'tablet' => ['colspan' => 1],
        ])
        ->and(array_keys($result->state->assets['main']))->toBe([0, 1, 2])
        ->and($result->state->assets['main'][1])->toBe([])
        ->and($result->state->originalAssets['main'][1])->toBe([])
        ->and($result->state->selectedRecords['main'][0])->toBe([])
        ->and($result->state->selectedRecords['main'][2])->toBe(['record']);
});

it('moves layout mutation history through typed actions', function (): void {
    $first = new LayoutBuilderStateData(
        containers: ['main' => ['widgets' => [['widget_key' => 'hero']], 'meta' => []]],
        assets: ['main' => [[]]],
        originalAssets: ['main' => [[]]],
        selectedRecords: ['main' => [[]]],
    );
    $second = new LayoutBuilderStateData(
        containers: ['main' => ['widgets' => [['widget_key' => 'cards']], 'meta' => []]],
        assets: ['main' => [[]]],
        originalAssets: ['main' => [[]]],
        selectedRecords: ['main' => [[]]],
    );

    $history = PushLayoutMutationSnapshotAction::run($first, []);
    $undo = UndoLayoutMutationSnapshotAction::run($second, $history->undoSnapshots, $history->redoSnapshots);
    $undoState = $undo->state;

    expect($undoState)->toBeInstanceOf(LayoutBuilderStateData::class);
    throw_unless($undoState instanceof LayoutBuilderStateData, RuntimeException::class, 'Undo state was not available.');

    $redo = RedoLayoutMutationSnapshotAction::run($undoState, $undo->history->undoSnapshots, $undo->history->redoSnapshots);

    expect($history->undoSnapshots)->toHaveCount(1)
        ->and($history->redoSnapshots)->toBe([])
        ->and($undo->changed())->toBeTrue()
        ->and($undo->state->containers['main']['widgets'][0]['widget_key'])->toBe('hero')
        ->and($undo->history->redoSnapshots)->toHaveCount(1)
        ->and($redo->changed())->toBeTrue()
        ->and($redo->state->containers['main']['widgets'][0]['widget_key'])->toBe('cards');
});

it('caps layout mutation history snapshots and clears redo on new mutations', function (): void {
    $state = new LayoutBuilderStateData(
        containers: ['main' => ['widgets' => [], 'meta' => []]],
        assets: ['main' => []],
        originalAssets: ['main' => []],
        selectedRecords: ['main' => []],
    );

    $undoSnapshots = [];

    for ($snapshotIndex = 0; $snapshotIndex < PushLayoutMutationSnapshotAction::MAX_HISTORY_DEPTH + 5; $snapshotIndex++) {
        $undoSnapshots = PushLayoutMutationSnapshotAction::run(
            new LayoutBuilderStateData(
                containers: ['main' => ['widgets' => [], 'meta' => ['snapshot' => $snapshotIndex]]],
                assets: ['main' => []],
                originalAssets: ['main' => []],
                selectedRecords: ['main' => []],
            ),
            $undoSnapshots,
        )->undoSnapshots;
    }

    $history = PushLayoutMutationSnapshotAction::run($state, $undoSnapshots);

    expect($history->undoSnapshots)->toHaveCount(PushLayoutMutationSnapshotAction::MAX_HISTORY_DEPTH)
        ->and($history->undoSnapshots[0]['containers']['main']['meta']['snapshot'])->toBe(6)
        ->and($history->redoSnapshots)->toBe([]);
});
