<?php

declare(strict_types=1);

use Capell\FrontendOptimizer\Actions\PersistRenderProfileAction;
use Capell\FrontendOptimizer\Actions\PrepareRenderProfileAction;
use Capell\FrontendOptimizer\Actions\ResolveRenderProfileAction;
use Capell\FrontendOptimizer\Enums\AssetLoadingStrategy;
use Capell\FrontendOptimizer\Enums\OptimizationScope;
use Capell\FrontendOptimizer\Enums\OptimizationStatus;
use Capell\FrontendOptimizer\Jobs\GenerateCriticalCssJob;
use Capell\FrontendOptimizer\Support\FrontendAssetSet;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

it('prepares a render profile and dispatches critical css generation when missing', function (): void {
    Storage::fake('local');
    Bus::fake();

    $profile = PrepareRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: ['layout' => 'landing'],
        assetSets: [
            FrontendAssetSet::make()
                ->css('hero', '/build/hero.css', AssetLoadingStrategy::Critical, criticalEligible: true),
        ],
        url: 'https://example.test/landing',
        label: 'Landing',
    );

    Storage::disk('local')->assertExists($profile->manifest['path']);
    Bus::assertDispatched(
        GenerateCriticalCssJob::class,
        fn (GenerateCriticalCssJob $job): bool => $job->renderProfileId === $profile->id
            && $job->url === 'https://example.test/landing',
    );
});

it('does not dispatch generation when the optimizer is disabled', function (): void {
    Storage::fake('local');
    Bus::fake();
    config()->set('capell-frontend-optimizer.enabled', false);

    PrepareRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: ['layout' => 'landing'],
        assetSets: [FrontendAssetSet::make()->css('hero', '/build/hero.css', AssetLoadingStrategy::Critical)],
        url: 'https://example.test/landing',
    );

    Bus::assertNotDispatched(GenerateCriticalCssJob::class);
});

it('dispatches generation again when the stored critical css file is missing', function (): void {
    Storage::fake('local');
    Bus::fake();

    $profileData = ResolveRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: ['layout' => 'landing'],
        assetSets: [FrontendAssetSet::make()->css('hero', '/build/hero.css', AssetLoadingStrategy::Critical)],
    );
    $profile = PersistRenderProfileAction::run($profileData);
    $profile->forceFill([
        'critical_css_path' => 'capell/frontend-optimizer/critical-css/missing.css',
        'status' => OptimizationStatus::Generated->value,
    ])->save();

    PrepareRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: ['layout' => 'landing'],
        assetSets: [FrontendAssetSet::make()->css('hero', '/build/hero.css', AssetLoadingStrategy::Critical)],
        url: 'https://example.test/landing',
    );

    Bus::assertDispatched(GenerateCriticalCssJob::class);
});
