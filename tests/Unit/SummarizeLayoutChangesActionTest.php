<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\SummarizeLayoutChangesAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;

it('summarizes container and responsive changes', function (): void {
    $baseline = new LayoutBuilderStateData(
        containers: ['main' => ['blocks' => [], 'meta' => ['colspan' => 12]]],
        assets: ['main' => []],
        originalAssets: ['main' => []],
        selectedRecords: ['main' => []],
    );

    $current = new LayoutBuilderStateData(
        containers: ['main' => ['blocks' => [], 'meta' => ['colspan' => 8, 'responsive' => ['mobile' => ['colspan' => 6]]]]],
        assets: ['main' => []],
        originalAssets: ['main' => []],
        selectedRecords: ['main' => []],
    );

    $changes = SummarizeLayoutChangesAction::run($baseline, $current);

    expect(array_column($changes, 'type'))->toBe(['container_resized', 'responsive_override_changed']);
});
