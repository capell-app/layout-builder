<?php

declare(strict_types=1);

use Capell\FrontendOptimizer\Enums\AssetLoadingStrategy;
use Capell\FrontendOptimizer\Support\FrontendAssetSet;
use Capell\FrontendOptimizer\Support\LayoutAssetRegistry;
use Capell\FrontendOptimizer\Support\WidgetAssetRegistry;

it('resolves layout asset sets by layout key', function (): void {
    $registry = new LayoutAssetRegistry;
    $registry->register('landing', FrontendAssetSet::make()->css('base', 'base.css'));

    expect($registry->resolve('landing')->all())->toHaveCount(1)
        ->and($registry->resolve('missing')->all())->toHaveCount(0);
});

it('resolves widget assets only when the instance condition passes', function (): void {
    $registry = new WidgetAssetRegistry;
    $registry->register(
        'asset-list',
        FrontendAssetSet::make()->js('carousel', 'carousel.js', AssetLoadingStrategy::Lazy),
        static fn (array $widgetData): bool => ($widgetData['display'] ?? null) === 'carousel',
    );

    expect($registry->resolve('asset-list', ['display' => 'grid'])->all())->toHaveCount(0)
        ->and($registry->resolve('asset-list', ['display' => 'carousel'])->all())->toHaveCount(1);
});
