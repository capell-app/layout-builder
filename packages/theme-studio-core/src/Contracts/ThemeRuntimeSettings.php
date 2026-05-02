<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Contracts;

use Capell\ThemeStudio\Core\Data\BrandProfileData;

interface ThemeRuntimeSettings
{
    public function activeTheme(): string;

    public function activePreset(): string;

    public function brandProfile(): BrandProfileData;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function themeOverrides(): array;
}
