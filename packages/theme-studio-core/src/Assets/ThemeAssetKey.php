<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Assets;

use Capell\ThemeStudio\Core\Data\BrandProfileData;

class ThemeAssetKey
{
    public static function make(string $themeKey, string $presetKey, BrandProfileData $brand): string
    {
        $hash = substr(hash('sha256', json_encode($brand->tokens(), JSON_THROW_ON_ERROR)), 0, 12);

        return implode(':', ['theme-studio', $themeKey, $presetKey, $hash]);
    }
}
