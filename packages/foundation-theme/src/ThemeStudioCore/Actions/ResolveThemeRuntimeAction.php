<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Actions;

use Capell\Core\Facades\CapellCore;
use Capell\ThemeStudio\Core\Assets\ThemeAssetKey;
use Capell\ThemeStudio\Core\Assets\ThemeTokenStore;
use Capell\ThemeStudio\Core\Data\BrandProfileData;
use Capell\ThemeStudio\Core\Data\ThemeDefinitionData;
use Capell\ThemeStudio\Core\Data\ThemePresetData;
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

        $resolvedBrand = $this->resolveLayeredBrand(
            brand: $brand,
            definition: $definition,
            registry: $registry,
            activePresetKey: $presetKey,
            themeOverrides: $themeOverrides,
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

    /**
     * @param  array<string, array<string, mixed>>  $themeOverrides
     */
    private function resolveLayeredBrand(
        BrandProfileData $brand,
        ThemeDefinitionData $definition,
        ThemeRegistry $registry,
        string $activePresetKey,
        array $themeOverrides,
    ): BrandProfileData {
        $definitions = array_reverse($this->definitionChain($definition, $registry));
        $defaultValues = [];
        $overrideValues = [];

        foreach ($definitions as $layerDefinition) {
            $preset = $this->presetForLayer($layerDefinition, $activePresetKey, $definition->key);

            if ($preset instanceof ThemePresetData) {
                $defaultValues = [
                    ...$defaultValues,
                    ...$preset->values,
                ];
            }
        }

        foreach ($definitions as $layerDefinition) {
            $overrideValues = [
                ...$overrideValues,
                ...($themeOverrides[$layerDefinition->key] ?? []),
            ];
        }

        return $brand
            ->merge($defaultValues)
            ->merge($overrideValues);
    }

    /**
     * @return array<int, ThemeDefinitionData>
     */
    private function definitionChain(ThemeDefinitionData $definition, ThemeRegistry $registry, array $visitedThemeKeys = []): array
    {
        if (in_array($definition->key, $visitedThemeKeys, true)) {
            return [$definition];
        }

        $visitedThemeKeys[] = $definition->key;
        $chain = [$definition];

        if (! CapellCore::hasPackage($definition->package)) {
            return $chain;
        }

        $extendsPackage = CapellCore::getPackage($definition->package)->getExtendsPackage();

        if ($extendsPackage === null || ! CapellCore::hasPackage($extendsPackage)) {
            return $chain;
        }

        $parentThemeKey = CapellCore::getPackage($extendsPackage)->getThemeKey();

        if ($parentThemeKey === null || ! $registry->has($parentThemeKey)) {
            return $chain;
        }

        return [
            ...$chain,
            ...$this->definitionChain($registry->definition($parentThemeKey), $registry, $visitedThemeKeys),
        ];
    }

    private function presetForLayer(
        ThemeDefinitionData $definition,
        string $activePresetKey,
        string $activeThemeKey,
    ): ?ThemePresetData {
        if ($definition->key === $activeThemeKey) {
            return $definition->preset($activePresetKey);
        }

        return $definition->preset($activePresetKey) ?? $definition->presets[0] ?? null;
    }
}
