<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Data;

use Capell\ThemeStudio\Core\Exceptions\ThemePresetNotFoundException;
use Spatie\LaravelData\Data;

class ThemeDefinitionData extends Data
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<int, string>  $bestFit
     * @param  array<int, string>  $includedSections
     * @param  array<string, string>  $assets
     * @param  array<int, ThemePresetData>  $presets
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public string $package,
        public string $previewImage,
        public array $tags,
        public array $bestFit,
        public array $includedSections,
        public array $presets,
        public array $assets = [],
    ) {}

    public function preset(string $key): ?ThemePresetData
    {
        foreach ($this->presets as $preset) {
            if ($preset->key === $key) {
                return $preset;
            }
        }

        return null;
    }

    public function presetOrFail(string $key): ThemePresetData
    {
        return $this->preset($key) ?? throw ThemePresetNotFoundException::forKey($this->key, $key);
    }

    /**
     * @return array<string, string>
     */
    public function presetOptions(): array
    {
        return collect($this->presets)
            ->mapWithKeys(fn (ThemePresetData $preset): array => [$preset->key => $preset->name])
            ->all();
    }
}
