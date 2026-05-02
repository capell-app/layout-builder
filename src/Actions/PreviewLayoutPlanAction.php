<?php

declare(strict_types=1);

namespace Capell\Mosaic\Actions;

use Capell\Mosaic\Data\LayoutPlanData;
use Capell\Mosaic\Data\LayoutPlanResultData;
use Capell\Mosaic\Support\LayoutPresets\LayoutPresetRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class PreviewLayoutPlanAction
{
    use AsObject;

    /**
     * @param  array<string, mixed>  $context
     */
    public function handle(string $prompt, array $context = []): LayoutPlanResultData
    {
        $preset = resolve(LayoutPresetRegistry::class)->bestMatch($prompt);

        return new LayoutPlanResultData(
            plan: new LayoutPlanData(
                prompt: $prompt,
                presetKey: $preset->key,
                containers: $preset->containers,
                sections: $preset->sections,
            ),
        );
    }
}
