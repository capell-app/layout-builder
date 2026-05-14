<?php

declare(strict_types=1);

use Capell\Core\Models\Widget;
use Capell\LayoutBuilder\Actions\AnalyzeLayoutDiagnosticsAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;

it('reports unknown widgets and invalid responsive metadata', function (): void {
    Widget::factory()->create(['key' => 'known']);

    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'known'],
                    ['widget_key' => 'missing'],
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
        ->and($diagnostics[0]->code)->toBe('unknown_widget')
        ->and($diagnostics[1]->severity)->toBe(LayoutDiagnosticSeverity::Warning)
        ->and($diagnostics[1]->code)->toBe('invalid_responsive_colspan');
});
