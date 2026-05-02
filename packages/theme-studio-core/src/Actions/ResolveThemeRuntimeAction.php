<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Actions;

use Capell\ThemeStudio\Core\Assets\ThemeAssetKey;
use Capell\ThemeStudio\Core\Assets\ThemeTokenStore;
use Capell\ThemeStudio\Core\Data\BrandProfileData;
use Capell\ThemeStudio\Core\Data\ThemeOverrideData;
use Capell\ThemeStudio\Core\Data\ThemeRuntimeData;
use Capell\ThemeStudio\Core\Exceptions\ThemePresetNotFoundException;
use Capell\ThemeStudio\Core\Preview\ThemePreviewContext;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class ResolveThemeRuntimeAction
{
    use AsObject;

    /**
     * @param  array<string, array<string, mixed>>  $themeOverrides
     */
    public function handle(
        string $activeTheme,
        string $activePreset,
        BrandProfileData $brand,
        array $themeOverrides = [],
        ?ThemePreviewContext $previewContext = null,
    ): ThemeRuntimeData {
        $registry = resolve(ThemeRegistry::class);
        $previewContext ??= resolve(ThemePreviewContext::class);

        $themeKey = $previewContext->themeKey ?? $activeTheme;
        $presetKey = $previewContext->presetKey ?? $activePreset;
        $definition = $registry->definition($themeKey);
        $preset = $definition->preset($presetKey);

        if ($preset === null) {
            throw ThemePresetNotFoundException::forKey($themeKey, $presetKey);
        }

        $resolvedBrand = ResolveBrandProfileAction::run(
            brand: $brand,
            definition: $definition,
            override: new ThemeOverrideData(
                themeKey: $themeKey,
                presetKey: $presetKey,
                values: $themeOverrides[$themeKey] ?? [],
            ),
        );

        return new ThemeRuntimeData(
            themeKey: $themeKey,
            presetKey: $presetKey,
            definition: $definition,
            preset: $preset,
            brand: $resolvedBrand,
            renderer: $registry->renderer($themeKey),
            assetKey: ThemeAssetKey::make($themeKey, $presetKey, $resolvedBrand),
            previewing: $previewContext->previewing,
            tokenCssPath: resolve(ThemeTokenStore::class)->put($themeKey, $presetKey, $resolvedBrand),
        );
    }
}
