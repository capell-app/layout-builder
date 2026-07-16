<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\LayoutPlanData;
use Capell\LayoutBuilder\Data\LayoutPlanResultData;
use Capell\LayoutBuilder\Support\LayoutPresets\LayoutPresetRegistry;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

class PreviewLayoutPlanAction
{
    use AsFake;
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
