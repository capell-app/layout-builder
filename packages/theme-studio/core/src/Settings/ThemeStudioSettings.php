<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Settings;

use Capell\ThemeStudio\Core\Contracts\ThemeRuntimeSettings;
use Capell\ThemeStudio\Core\Data\BrandProfileData;
use Spatie\LaravelSettings\Settings;

class ThemeStudioSettings extends Settings implements ThemeRuntimeSettings
{
    public string $activeTheme = 'corporate';

    public string $activePreset = 'boardroom';

    public ?string $draftTheme = null;

    public ?string $draftPreset = null;

    public ?int $draftWorkspaceId = null;

    public array $brandProfile = [
        'primaryColor' => '#1a2d6d',
        'accentColor' => '#f59e0b',
        'neutralColor' => '#111827',
        'headingFont' => 'inter',
        'bodyFont' => 'inter',
        'spacing' => 'balanced',
        'alignment' => 'left',
        'cardStyle' => 'subtle',
        'navigationStyle' => 'standard',
        'layoutPresentation' => 'structured',
        'motionIntensity' => 'subtle',
        'mediaTreatment' => 'natural',
    ];

    public array $themeOverrides = [];

    public static function group(): string
    {
        return 'theme_studio';
    }

    public function activeTheme(): string
    {
        return $this->activeTheme;
    }

    public function activePreset(): string
    {
        return $this->activePreset;
    }

    public function brandProfile(): BrandProfileData
    {
        return BrandProfileData::from($this->brandProfile);
    }

    public function themeOverrides(): array
    {
        return $this->themeOverrides;
    }
}
