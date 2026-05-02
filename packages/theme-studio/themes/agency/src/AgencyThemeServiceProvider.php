<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Agency;

use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\ThemeStudio\Core\Data\ThemeDefinitionData;
use Capell\ThemeStudio\Core\Data\ThemePresetData;
use Capell\ThemeStudio\Core\Rendering\BladeThemeRenderer;
use Capell\ThemeStudio\Core\Rendering\ViewSectionRenderer;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Illuminate\Support\ServiceProvider;

class AgencyThemeServiceProvider extends ServiceProvider
{
    public const THEME_KEY = 'agency';

    public static function definition(): ThemeDefinitionData
    {
        return new ThemeDefinitionData(
            key: self::THEME_KEY,
            name: 'Agency',
            description: 'Expressive layouts with bold rhythm, immersive media, and confident calls to action.',
            package: 'capell-app/theme-agency',
            previewImage: '/vendor/capell/theme-studio/agency-signal.jpg',
            tags: ['Expressive', 'Portfolio', 'Creative'],
            bestFit: ['Studios', 'Agencies', 'Brand-led teams'],
            includedSections: ['navigation', 'hero', 'features', 'proof', 'content-listing', 'cta', 'footer'],
            presets: [
                new ThemePresetData(
                    key: 'signal',
                    name: 'Signal',
                    description: 'Sharp contrast, strong statements, and energetic section pacing.',
                    previewImage: '/vendor/capell/theme-studio/agency-signal.jpg',
                    values: [
                        'primaryColor' => '#ff5a7e',
                        'accentColor' => '#3b82f6',
                        'headingFont' => 'sora',
                        'spacing' => 'spacious',
                        'cardStyle' => 'layered',
                        'layoutPresentation' => 'immersive',
                        'motionIntensity' => 'expressive',
                    ],
                ),
                new ThemePresetData(
                    key: 'gallery',
                    name: 'Gallery',
                    description: 'Media-forward presentation with calmer motion and framed project surfaces.',
                    previewImage: '/vendor/capell/theme-studio/agency-gallery.jpg',
                    values: [
                        'primaryColor' => '#7c3aed',
                        'accentColor' => '#fb7185',
                        'headingFont' => 'manrope',
                        'spacing' => 'spacious',
                        'cardStyle' => 'elevated',
                        'mediaTreatment' => 'framed',
                        'layoutPresentation' => 'editorial',
                    ],
                ),
                new ThemePresetData(
                    key: 'atelier',
                    name: 'Atelier',
                    description: 'Editorial studio feel with soft neutrals and refined proof.',
                    previewImage: '/vendor/capell/theme-studio/agency-atelier.jpg',
                    values: [
                        'primaryColor' => '#be123c',
                        'accentColor' => '#f97316',
                        'headingFont' => 'playfair',
                        'spacing' => 'balanced',
                        'cardStyle' => 'subtle',
                        'layoutPresentation' => 'editorial',
                        'motionIntensity' => 'subtle',
                    ],
                ),
            ],
            assets: ['css' => 'vendor/capell/theme-studio/agency.css'],
        );
    }

    public function boot(ThemeRegistry $registry): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'capell-theme-agency');

        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../resources/views/sections');

        $sectionRenderers = $this->sectionRenderers();

        $registry->register(
            definition: self::definition(),
            themeRenderer: new BladeThemeRenderer(
                themeKey: self::THEME_KEY,
                layoutView: 'capell-theme-agency::page',
                sectionRenderers: $sectionRenderers,
            ),
            sectionRenderers: array_values($sectionRenderers),
        );
    }

    /**
     * @return array<string, ViewSectionRenderer>
     */
    private function sectionRenderers(): array
    {
        return [
            'navigation' => new ViewSectionRenderer(self::THEME_KEY, 'navigation', 'capell-theme-agency::sections.navigation', failLoudly: true),
            'hero' => new ViewSectionRenderer(self::THEME_KEY, 'hero', 'capell-theme-agency::sections.hero', failLoudly: true),
            'features' => new ViewSectionRenderer(self::THEME_KEY, 'features', 'capell-theme-agency::sections.features', failLoudly: true),
            'proof' => new ViewSectionRenderer(self::THEME_KEY, 'proof', 'capell-theme-agency::sections.proof', failLoudly: true),
            'content-listing' => new ViewSectionRenderer(self::THEME_KEY, 'content-listing', 'capell-theme-agency::sections.content-listing', failLoudly: true),
            'cta' => new ViewSectionRenderer(self::THEME_KEY, 'cta', 'capell-theme-agency::sections.cta', failLoudly: true),
            'footer' => new ViewSectionRenderer(self::THEME_KEY, 'footer', 'capell-theme-agency::sections.footer', failLoudly: true),
        ];
    }
}
