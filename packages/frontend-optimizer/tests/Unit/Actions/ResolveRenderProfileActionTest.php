<?php

declare(strict_types=1);

use Capell\FrontendOptimizer\Actions\ResolveRenderProfileAction;
use Capell\FrontendOptimizer\Enums\AssetLoadingStrategy;
use Capell\FrontendOptimizer\Enums\OptimizationScope;
use Capell\FrontendOptimizer\Support\FrontendAssetSet;

it('creates a deterministic render profile hash from normalized context and assets', function (): void {
    $first = ResolveRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: [
            'theme' => ['updated_at' => '2026-05-07', 'key' => 'default'],
            'layout' => 'landing',
        ],
        assetSets: [
            FrontendAssetSet::make()->css('hero', 'hero.css', AssetLoadingStrategy::Preload),
        ],
        label: 'Landing',
    );

    $second = ResolveRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: [
            'layout' => 'landing',
            'theme' => ['key' => 'default', 'updated_at' => '2026-05-07'],
        ],
        assetSets: [
            FrontendAssetSet::make()->css('hero', 'hero.css', AssetLoadingStrategy::Preload),
        ],
        label: 'Landing',
    );

    expect($first->hash)->toBe($second->hash)
        ->and($first->manifest()['scope'])->toBe('layout')
        ->and($first->manifest()['assets'])->toHaveCount(1);
});

it('changes the render profile hash when widget assets change', function (): void {
    $first = ResolveRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: ['layout' => 'landing'],
        assetSets: [FrontendAssetSet::make()->css('hero', 'hero.css')],
    );

    $second = ResolveRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: ['layout' => 'landing'],
        assetSets: [FrontendAssetSet::make()->css('carousel', 'carousel.css')],
    );

    expect($first->hash)->not()->toBe($second->hash);
});
