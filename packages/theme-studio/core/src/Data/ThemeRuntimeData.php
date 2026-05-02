<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Data;

use Capell\ThemeStudio\Core\Contracts\ThemeRenderer;
use Spatie\LaravelData\Data;

class ThemeRuntimeData extends Data
{
    public function __construct(
        public string $themeKey,
        public string $presetKey,
        public ThemeDefinitionData $definition,
        public ThemePresetData $preset,
        public BrandProfileData $brand,
        public ThemeRenderer $renderer,
        public string $assetKey,
        public bool $previewing = false,
        public ?string $tokenCssPath = null,
    ) {}
}
