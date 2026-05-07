<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Assets;

use Capell\ThemeStudio\Core\Data\BrandProfileData;
use Illuminate\Support\Facades\File;

class ThemeTokenStore
{
    public function __construct(private readonly ?string $directory = null) {}

    public function put(string $themeKey, string $presetKey, BrandProfileData $brand): string
    {
        $directory = $this->directory ?? public_path('vendor/capell-theme/tokens');

        File::ensureDirectoryExists($directory);

        $filename = str_replace(':', '-', ThemeAssetKey::make($themeKey, $presetKey, $brand)) . '.css';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        File::put($path, (new ThemeTokenRenderer)->css($brand));

        return $path;
    }
}
