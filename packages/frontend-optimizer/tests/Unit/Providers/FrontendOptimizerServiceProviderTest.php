<?php

declare(strict_types=1);

use Capell\FrontendOptimizer\Contracts\CriticalCssGenerator;
use Capell\FrontendOptimizer\Support\LayoutAssetRegistry;
use Capell\FrontendOptimizer\Support\PlaywrightCriticalCssGenerator;
use Capell\FrontendOptimizer\Support\WidgetAssetRegistry;

it('binds optimizer registries and the required playwright generator', function (): void {
    expect(resolve(LayoutAssetRegistry::class))->toBeInstanceOf(LayoutAssetRegistry::class)
        ->and(resolve(WidgetAssetRegistry::class))->toBeInstanceOf(WidgetAssetRegistry::class)
        ->and(resolve(CriticalCssGenerator::class))->toBeInstanceOf(PlaywrightCriticalCssGenerator::class);
});
