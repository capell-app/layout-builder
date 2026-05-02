<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Actions;

use Capell\ThemeStudio\Core\Data\BrandProfileData;
use Capell\ThemeStudio\Core\Data\ThemeDefinitionData;
use Capell\ThemeStudio\Core\Data\ThemeOverrideData;

class ResolveBrandProfileAction
{
    public static function run(
        BrandProfileData $brand,
        ThemeDefinitionData $definition,
        ThemeOverrideData $override,
    ): BrandProfileData {
        return (new self)->handle($brand, $definition, $override);
    }

    public function handle(
        BrandProfileData $brand,
        ThemeDefinitionData $definition,
        ThemeOverrideData $override,
    ): BrandProfileData {
        $presetValues = [];

        if ($override->presetKey !== null) {
            $presetValues = $definition->preset($override->presetKey)?->values ?? [];
        }

        return $brand
            ->merge($presetValues)
            ->merge($override->values);
    }
}
