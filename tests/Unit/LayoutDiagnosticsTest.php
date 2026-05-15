<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\AnalyzeLayoutDiagnosticsAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Models\Element;

it('reports unknown elements and invalid responsive metadata', function (): void {
    Element::factory()->create(['key' => 'known']);

    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'elements' => [
                    ['element_key' => 'known'],
                    ['element_key' => 'missing'],
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
        ->and($diagnostics[0]->code)->toBe('unknown_element')
        ->and($diagnostics[1]->severity)->toBe(LayoutDiagnosticSeverity::Warning)
        ->and($diagnostics[1]->code)->toBe('invalid_responsive_colspan');
});
