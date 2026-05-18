<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\AnalyzeLayoutDiagnosticsAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Models\Block;

it('reports unknown blocks and invalid responsive metadata', function (): void {
    Block::factory()->create(['key' => 'known']);

    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'blocks' => [
                    ['block_key' => 'known'],
                    ['block_key' => 'missing'],
                ],
                'meta' => [
                    'responsive' => [
                        'mobile' => ['colspan' => 20],
                    ],
                ],
            ],
        ],
        assets: ['main' => []],
        originalAssets: ['main' => []],
        selectedRecords: ['main' => []],
    );

    $diagnostics = AnalyzeLayoutDiagnosticsAction::run($state);

    expect($diagnostics)->toHaveCount(2)
        ->and($diagnostics[0]->severity)->toBe(LayoutDiagnosticSeverity::Blocking)
        ->and($diagnostics[0]->code)->toBe('unknown_block')
        ->and($diagnostics[1]->severity)->toBe(LayoutDiagnosticSeverity::Warning)
        ->and($diagnostics[1]->code)->toBe('invalid_responsive_colspan');
});
