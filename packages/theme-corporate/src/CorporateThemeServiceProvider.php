<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Corporate;

use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\ThemeStudio\Core\Data\ThemeDefinitionData;
use Capell\ThemeStudio\Core\Data\ThemePresetData;
use Capell\ThemeStudio\Core\Rendering\BladeThemeRenderer;
use Capell\ThemeStudio\Core\Rendering\ViewSectionRenderer;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Illuminate\Support\ServiceProvider;

class CorporateThemeServiceProvider extends ServiceProvider
{
    public const THEME_KEY = 'corporate';

    public static string $packageName = 'capell-app/theme-corporate';

    public static function definition(): ThemeDefinitionData
    {
        return new ThemeDefinitionData(
            key: self::THEME_KEY,
            name: 'Corporate',
            description: 'Trust-led layouts with restrained hierarchy, formal navigation, and structured proof.',
            package: 'capell-app/theme-corporate',
            previewImage: '/vendor/capell/themes/corporate-boardroom.jpg',
            tags: ['Trust', 'Clarity', 'B2B'],
            bestFit: ['Professional services', 'Public sector', 'Established businesses'],
            includedSections: ['navigation', 'hero', 'features', 'proof', 'content-listing', 'cta', 'footer'],
            presets: [
                new ThemePresetData(
                    key: 'boardroom',
                    name: 'Boardroom',
                    description: 'Deep navy, measured spacing, and formal card structure.',
                    previewImage: '/vendor/capell/themes/corporate-boardroom.jpg',
                    values: [
                        'primaryColor' => '#1a2d6d',
                        'accentColor' => '#f59e0b',
                        'headingFont' => 'playfair',
                        'cardStyle' => 'bordered',
                        'navigationStyle' => 'standard',
                        'layoutPresentation' => 'structured',
                    ],
                ),
                new ThemePresetData(
                    key: 'civic',
                    name: 'Civic',
                    description: 'Accessible contrast, calm typography, and clear information hierarchy.',
                    previewImage: '/vendor/capell/themes/corporate-civic.jpg',
                    values: [
                        'primaryColor' => '#0f766e',
                        'accentColor' => '#facc15',
                        'headingFont' => 'inter',
                        'cardStyle' => 'subtle',
                        'navigationStyle' => 'prominent',
                        'layoutPresentation' => 'structured',
                    ],
                ),
                new ThemePresetData(
                    key: 'advisory',
                    name: 'Advisory',
                    description: 'Editorial trust signals with generous whitespace and refined proof blocks.',
                    previewImage: '/vendor/capell/themes/corporate-advisory.jpg',
                    values: [
                        'primaryColor' => '#312e81',
                        'accentColor' => '#c084fc',
                        'headingFont' => 'manrope',
                        'cardStyle' => 'elevated',
                        'navigationStyle' => 'minimal',
                        'layoutPresentation' => 'editorial',
                    ],
                ),
            ],
            assets: ['css' => 'vendor/capell/themes/corporate.css'],
        );
    }

    public function register(): void
    {
        CapellCore::registerPackage(
            name: self::$packageName,
            type: PackageTypeEnum::Theme,
            path: realpath(__DIR__ . '/..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
        );
    }

    public function boot(ThemeRegistry $registry): void
    {
        if (! CapellCore::isPackageInstalled(self::$packageName)) {
            return;
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'capell-theme-corporate');

        $sectionRenderers = $this->sectionRenderers();

        $registry->register(
            definition: self::definition(),
            themeRenderer: new BladeThemeRenderer(
                themeKey: self::THEME_KEY,
                layoutView: 'capell::page',
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
            'navigation' => new ViewSectionRenderer(self::THEME_KEY, 'navigation', 'capell-theme-corporate::sections.navigation', failLoudly: true),
            'hero' => new ViewSectionRenderer(self::THEME_KEY, 'hero', 'capell-theme-corporate::sections.hero', failLoudly: true),
            'features' => new ViewSectionRenderer(self::THEME_KEY, 'features', 'capell-theme-corporate::sections.features', failLoudly: true),
            'proof' => new ViewSectionRenderer(self::THEME_KEY, 'proof', 'capell-theme-corporate::sections.proof', failLoudly: true),
            'content-listing' => new ViewSectionRenderer(self::THEME_KEY, 'content-listing', 'capell-theme-corporate::sections.content-listing', failLoudly: true),
            'cta' => new ViewSectionRenderer(self::THEME_KEY, 'cta', 'capell-theme-corporate::sections.cta', failLoudly: true),
            'footer' => new ViewSectionRenderer(self::THEME_KEY, 'footer', 'capell-theme-corporate::sections.footer', failLoudly: true),
        ];
    }
}
