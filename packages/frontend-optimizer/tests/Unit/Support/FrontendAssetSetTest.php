<?php

declare(strict_types=1);

use Capell\FrontendOptimizer\Enums\AssetKind;
use Capell\FrontendOptimizer\Enums\AssetLoadingStrategy;
use Capell\FrontendOptimizer\Enums\AssetSlot;
use Capell\FrontendOptimizer\Support\FrontendAssetSet;

it('collects css and js assets with loading strategies', function (): void {
    $assets = FrontendAssetSet::make()
        ->css(
            handle: 'hero',
            path: 'vendor/theme/hero.css',
            loadingStrategy: AssetLoadingStrategy::Preload,
            slot: AssetSlot::AboveFold,
            criticalEligible: true,
            packageName: 'capell-app/theme',
        )
        ->js(
            handle: 'carousel',
            path: 'vendor/layout/carousel.js',
            loadingStrategy: AssetLoadingStrategy::Lazy,
            packageName: 'capell-app/layout-builder',
        )
        ->all();

    expect($assets)->toHaveCount(2)
        ->and($assets[0]->kind)->toBe(AssetKind::Js)
        ->and($assets[0]->loadingStrategy)->toBe(AssetLoadingStrategy::Lazy)
        ->and($assets[1]->criticalEligible)->toBeTrue()
        ->and($assets[1]->slot)->toBe(AssetSlot::AboveFold);
});

it('rejects critical javascript assets', function (): void {
    FrontendAssetSet::make()->js(
        handle: 'bad',
        path: 'bad.js',
        loadingStrategy: AssetLoadingStrategy::Critical,
    );
})->throws(InvalidArgumentException::class, 'JavaScript assets cannot use the critical loading strategy.');
