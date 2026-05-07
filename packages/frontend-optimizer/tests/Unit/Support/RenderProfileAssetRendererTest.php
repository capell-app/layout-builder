<?php

declare(strict_types=1);

use Capell\FrontendOptimizer\Actions\PersistRenderProfileAction;
use Capell\FrontendOptimizer\Actions\ResolveRenderProfileAction;
use Capell\FrontendOptimizer\Enums\AssetLoadingStrategy;
use Capell\FrontendOptimizer\Enums\AssetSlot;
use Capell\FrontendOptimizer\Enums\OptimizationScope;
use Capell\FrontendOptimizer\Enums\OptimizationStatus;
use Capell\FrontendOptimizer\Support\FrontendAssetSet;
use Capell\FrontendOptimizer\Support\RenderProfileAssetRenderer;
use Illuminate\Support\Facades\Storage;

it('renders only profile assets and inline critical css when available', function (): void {
    Storage::fake('local');

    $profileData = ResolveRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: ['layout' => 'landing'],
        assetSets: [
            FrontendAssetSet::make()
                ->css('base', '/build/base.css', AssetLoadingStrategy::Blocking)
                ->css('carousel', '/build/carousel.css', AssetLoadingStrategy::Preload)
                ->js('carousel', '/build/carousel.js', AssetLoadingStrategy::Lazy),
        ],
    );

    $profile = PersistRenderProfileAction::run($profileData);
    $profile->forceFill([
        'critical_css_path' => 'capell/frontend-optimizer/critical-css/test.css',
        'status' => OptimizationStatus::Generated->value,
    ])->save();

    Storage::disk('local')->put('capell/frontend-optimizer/critical-css/test.css', '.hero { display: grid; }');

    $html = resolve(RenderProfileAssetRenderer::class)->render($profile->hash)->toHtml();

    expect($html)->toContain('<style>.hero { display: grid; }</style>')
        ->and($html)->toContain('<link rel="stylesheet" href="/build/base.css">')
        ->and($html)->toContain('<link rel="preload" as="style" href="/build/carousel.css"')
        ->and($html)->toContain('<script type="module" defer src="/build/carousel.js"></script>')
        ->and($html)->not->toContain('capell')
        ->and($html)->not->toContain('editor')
        ->and($html)->not->toContain('signed');
});

it('falls back to normal critical stylesheets until generated css is available', function (): void {
    Storage::fake('local');

    $profileData = ResolveRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: ['layout' => 'landing'],
        assetSets: [
            FrontendAssetSet::make()
                ->css(
                    handle: 'hero',
                    path: '/build/hero.css',
                    loadingStrategy: AssetLoadingStrategy::Critical,
                    slot: AssetSlot::AboveFold,
                    criticalEligible: true,
                ),
        ],
    );

    $profile = PersistRenderProfileAction::run($profileData);

    $html = resolve(RenderProfileAssetRenderer::class)->render($profile->hash)->toHtml();

    expect($html)->toContain('<link rel="stylesheet" href="/build/hero.css">')
        ->and($html)->not->toContain('<style>');
});

it('escapes inline critical css style terminators', function (): void {
    Storage::fake('local');

    $profileData = ResolveRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: ['layout' => 'landing'],
        assetSets: [FrontendAssetSet::make()->css('hero', '/build/hero.css', AssetLoadingStrategy::Critical)],
    );

    $profile = PersistRenderProfileAction::run($profileData);
    $profile->forceFill([
        'critical_css_path' => 'capell/frontend-optimizer/critical-css/test.css',
        'status' => OptimizationStatus::Generated->value,
    ])->save();

    Storage::disk('local')->put('capell/frontend-optimizer/critical-css/test.css', '.hero::after { content: "</style>"; }');

    $html = resolve(RenderProfileAssetRenderer::class)->render($profile->hash)->toHtml();

    expect($html)->toContain('<\\/style>')
        ->and($html)->not->toContain('</style>"; }');
});
