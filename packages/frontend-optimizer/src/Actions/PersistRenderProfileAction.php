<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Actions;

use Capell\FrontendOptimizer\Data\RenderProfileData;
use Capell\FrontendOptimizer\Enums\OptimizationStatus;
use Capell\FrontendOptimizer\Models\FrontendRenderProfile;
use Lorisleiva\Actions\Concerns\AsAction;

class PersistRenderProfileAction
{
    use AsAction;

    public function handle(RenderProfileData $profile, ?string $manifestPath = null): FrontendRenderProfile
    {
        $renderProfile = FrontendRenderProfile::query()->firstOrNew(['hash' => $profile->hash]);

        $renderProfile->fill([
            'label' => $profile->label,
            'manifest' => $manifestPath === null ? null : ['path' => $manifestPath],
            'scope' => $profile->scope->value,
            'signature' => $profile->signature,
        ]);

        if (! $renderProfile->exists) {
            $renderProfile->fill([
                'critical_css_path' => null,
                'status' => OptimizationStatus::Pending->value,
            ]);
        }

        $renderProfile->save();

        return $renderProfile;
    }
}
