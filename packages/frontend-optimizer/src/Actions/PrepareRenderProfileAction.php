<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Actions;

use Capell\FrontendOptimizer\Enums\OptimizationScope;
use Capell\FrontendOptimizer\Enums\OptimizationStatus;
use Capell\FrontendOptimizer\Jobs\GenerateCriticalCssJob;
use Capell\FrontendOptimizer\Models\FrontendRenderProfile;
use Capell\FrontendOptimizer\Support\FrontendAssetSet;
use Illuminate\Contracts\Filesystem\Factory;
use Lorisleiva\Actions\Concerns\AsAction;

class PrepareRenderProfileAction
{
    use AsAction;

    public function __construct(private readonly Factory $filesystems) {}

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, FrontendAssetSet>  $assetSets
     */
    public function handle(
        OptimizationScope $scope,
        array $context,
        array $assetSets,
        string $url,
        ?string $label = null,
    ): FrontendRenderProfile {
        $profileData = ResolveRenderProfileAction::run(
            scope: $scope,
            context: $context,
            assetSets: $assetSets,
            label: $label,
        );

        $manifestPath = StoreRenderProfileManifestAction::run($profileData);
        $profile = PersistRenderProfileAction::run($profileData, $manifestPath);

        if ($this->shouldDispatchGeneration($profile)) {
            dispatch(new GenerateCriticalCssJob($profile->id, $url));
        }

        return $profile;
    }

    private function shouldDispatchGeneration(FrontendRenderProfile $profile): bool
    {
        if (config('capell-frontend-optimizer.enabled', true) !== true) {
            return false;
        }

        if (! is_string($profile->critical_css_path) || $profile->critical_css_path === '') {
            return true;
        }

        if (! $this->filesystems->disk('local')->exists($profile->critical_css_path)) {
            return true;
        }

        return $profile->status === OptimizationStatus::Failed->value;
    }
}
