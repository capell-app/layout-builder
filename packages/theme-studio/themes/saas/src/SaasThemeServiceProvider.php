<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Saas;

use Capell\ThemeStudio\Core\Data\ThemeDefinitionData;
use Capell\ThemeStudio\Core\Data\ThemePresetData;
use Capell\ThemeStudio\Core\Rendering\BladeThemeRenderer;
use Capell\ThemeStudio\Core\Rendering\ViewSectionRenderer;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Illuminate\Support\ServiceProvider;

class SaasThemeServiceProvider extends ServiceProvider
{
    public const THEME_KEY = 'saas';

    public static function definition(): ThemeDefinitionData
    {
        return new ThemeDefinitionData(
            key: self::THEME_KEY,
            name: 'SaaS',
            description: 'Conversion-led layouts with product framing, compact proof, and clear feature hierarchy.',
            package: 'capell-app/theme-saas',
            previewImage: '/vendor/capell/theme-studio/saas-launch.jpg',
            tags: ['Product', 'Conversion', 'Growth'],
            bestFit: ['Software products', 'Startups', 'Subscription services'],
            includedSections: ['navigation', 'hero', 'features', 'proof', 'content-listing', 'cta', 'footer'],
            presets: [
                new ThemePresetData(
                    key: 'launch',
                    name: 'Launch',
                    description: 'High-conversion product framing with crisp cards and proof near the fold.',
                    previewImage: '/vendor/capell/theme-studio/saas-launch.jpg',
                    values: [
                        'primaryColor' => '#6366f1',
                        'accentColor' => '#10b981',
                        'headingFont' => 'inter',
                        'cardStyle' => 'elevated',
                        'navigationStyle' => 'prominent',
                        'layoutPresentation' => 'structured',
                    ],
                ),
                new ThemePresetData(
                    key: 'platform',
                    name: 'Platform',
                    description: 'Enterprise SaaS positioning with denser proof and measured motion.',
                    previewImage: '/vendor/capell/theme-studio/saas-platform.jpg',
                    values: [
                        'primaryColor' => '#2563eb',
                        'accentColor' => '#14b8a6',
                        'headingFont' => 'manrope',
                        'cardStyle' => 'bordered',
                        'navigationStyle' => 'standard',
                        'motionIntensity' => 'subtle',
                    ],
                ),
                new ThemePresetData(
                    key: 'labs',
                    name: 'Labs',
                    description: 'More expressive product storytelling for AI, developer, and beta products.',
                    previewImage: '/vendor/capell/theme-studio/saas-labs.jpg',
                    values: [
                        'primaryColor' => '#7c3aed',
                        'accentColor' => '#22d3ee',
                        'headingFont' => 'sora',
                        'cardStyle' => 'layered',
                        'layoutPresentation' => 'immersive',
                        'motionIntensity' => 'expressive',
                        'mediaTreatment' => 'framed',
                    ],
                ),
            ],
            assets: ['css' => 'vendor/capell/theme-studio/saas.css'],
        );
    }

    public function boot(ThemeRegistry $registry): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'capell-theme-saas');

        $sectionRenderers = $this->sectionRenderers();

        $registry->register(
            definition: self::definition(),
            themeRenderer: new BladeThemeRenderer(
                themeKey: self::THEME_KEY,
                layoutView: 'capell-theme-saas::page',
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
            'navigation' => new ViewSectionRenderer(self::THEME_KEY, 'navigation', 'capell-theme-saas::sections.navigation', failLoudly: true),
            'hero' => new ViewSectionRenderer(self::THEME_KEY, 'hero', 'capell-theme-saas::sections.hero', failLoudly: true),
            'features' => new ViewSectionRenderer(self::THEME_KEY, 'features', 'capell-theme-saas::sections.features', failLoudly: true),
            'proof' => new ViewSectionRenderer(self::THEME_KEY, 'proof', 'capell-theme-saas::sections.proof', failLoudly: true),
            'content-listing' => new ViewSectionRenderer(self::THEME_KEY, 'content-listing', 'capell-theme-saas::sections.content-listing', failLoudly: true),
            'cta' => new ViewSectionRenderer(self::THEME_KEY, 'cta', 'capell-theme-saas::sections.cta', failLoudly: true),
            'footer' => new ViewSectionRenderer(self::THEME_KEY, 'footer', 'capell-theme-saas::sections.footer', failLoudly: true),
        ];
    }
}
