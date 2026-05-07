<?php

declare(strict_types=1);

use Capell\FrontendOptimizer\Actions\PersistRenderProfileAction;
use Capell\FrontendOptimizer\Actions\ResolveRenderProfileAction;
use Capell\FrontendOptimizer\Enums\OptimizationScope;
use Capell\FrontendOptimizer\Enums\OptimizationStatus;
use Capell\FrontendOptimizer\Support\FrontendAssetSet;

it('preserves generated critical css state when the same profile is persisted again', function (): void {
    $profileData = ResolveRenderProfileAction::run(
        scope: OptimizationScope::Layout,
        context: ['layout' => 'landing'],
        assetSets: [FrontendAssetSet::make()->css('base', '/build/base.css')],
    );

    $profile = PersistRenderProfileAction::run($profileData, 'capell/frontend-optimizer/manifests/old.json');
    $profile->forceFill([
        'critical_css_path' => 'capell/frontend-optimizer/critical-css/profile.css',
        'generated_at' => now(),
        'status' => OptimizationStatus::Generated->value,
    ])->save();

    $persistedProfile = PersistRenderProfileAction::run($profileData, 'capell/frontend-optimizer/manifests/new.json');

    expect($persistedProfile->critical_css_path)->toBe('capell/frontend-optimizer/critical-css/profile.css')
        ->and($persistedProfile->status)->toBe(OptimizationStatus::Generated->value)
        ->and($persistedProfile->manifest)->toBe(['path' => 'capell/frontend-optimizer/manifests/new.json']);
});
