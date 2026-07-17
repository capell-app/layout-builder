<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\Mutations\ResizeLayoutContainerAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;

it('resizes base and responsive container spans', function (): void {
    $state = new LayoutBuilderStateData(
        containers: ['main' => ['widgets' => [], 'meta' => ['colspan' => 12]]],
        assets: ['main' => []],
        originalAssets: ['main' => []],
        selectedRecords: ['main' => []],
    );

    $baseResult = ResizeLayoutContainerAction::run($state, 'main', 8, null);
    $mobileResult = ResizeLayoutContainerAction::run($baseResult->state, 'main', 6, LayoutBreakpoint::Mobile);

    $responsive = $mobileResult->state->containerMeta('main')['responsive'] ?? null;
    throw_unless(is_array($responsive), RuntimeException::class, 'Expected responsive container settings.');
    $mobile = $responsive['mobile'] ?? null;
    throw_unless(is_array($mobile), RuntimeException::class, 'Expected mobile container settings.');

    expect($baseResult->state->containerMeta('main')['colspan'])->toBe(8)
        ->and($mobile['colspan'])->toBe(6);
});
