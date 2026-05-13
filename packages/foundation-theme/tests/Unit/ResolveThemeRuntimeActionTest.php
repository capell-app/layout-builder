<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Manifest\CapellManifestData;
use Capell\Core\ThemeStudio\Actions\ResolveThemeRuntimeAction;
use Capell\Core\ThemeStudio\Assets\ThemeTokenStore;
use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Data\ThemePresetData;
use Capell\Core\ThemeStudio\Rendering\BladeThemeRenderer;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;

it('layers parent defaults before child defaults and applies database overrides last', function (): void {
    CapellCore::registerManifestPackage(CapellManifestData::fromArray([
        'manifest-version' => 3,
        'name' => 'vendor/base-theme',
        'slug' => 'base-theme',
        'displayName' => 'Base Theme',
        'kind' => 'theme',
        'capellApiVersion' => '^4.0',
        'version' => '1.0.0',
        'product' => [
            'group' => 'Theme',
            'tier' => 'free',
        ],
        'performance' => [
            'cacheSafety' => [
                'cacheable' => true,
                'variesBy' => [],
                'sensitiveOutput' => false,
                'invalidationSources' => [],
                'queueInvalidation' => false,
            ],
        ],
        'surfaces' => ['frontend'],
        'themeKey' => 'base',
    ]));
    CapellCore::registerManifestPackage(CapellManifestData::fromArray([
        'manifest-version' => 3,
        'name' => 'vendor/child-theme',
        'slug' => 'child-theme',
        'displayName' => 'Child Theme',
        'kind' => 'theme',
        'capellApiVersion' => '^4.0',
        'version' => '1.0.0',
        'product' => [
            'group' => 'Theme',
            'tier' => 'free',
        ],
        'performance' => [
            'cacheSafety' => [
                'cacheable' => true,
                'variesBy' => [],
                'sensitiveOutput' => false,
                'invalidationSources' => [],
                'queueInvalidation' => false,
            ],
        ],
        'surfaces' => ['frontend'],
        'dependencies' => [
            'requires' => ['vendor/base-theme'],
        ],
        'extends' => 'vendor/base-theme',
        'themeKey' => 'child',
    ]));

    $registry = new ThemeRegistry;
    app()->instance(ThemeRegistry::class, $registry);
    $renderer = new BladeThemeRenderer('child', 'missing-view', []);

    $registry->register(
        definition: new ThemeDefinitionData(
            key: 'base',
            name: 'Base',
            description: 'Base theme',
            package: 'vendor/base-theme',
            previewImage: '',
            tags: [],
            bestFit: [],
            includedSections: [],
            presets: [
                new ThemePresetData(
                    key: 'base-default',
                    name: 'Base default',
                    description: '',
                    previewImage: '',
                    values: [
                        'primaryColor' => '#111111',
                        'accentColor' => '#222222',
                        'cardStyle' => 'bordered',
                    ],
                ),
            ],
        ),
        themeRenderer: $renderer,
        sectionRenderers: [],
    );
    $registry->register(
        definition: new ThemeDefinitionData(
            key: 'child',
            name: 'Child',
            description: 'Child theme',
            package: 'vendor/child-theme',
            previewImage: '',
            tags: [],
            bestFit: [],
            includedSections: [],
            presets: [
                new ThemePresetData(
                    key: 'launch',
                    name: 'Launch',
                    description: '',
                    previewImage: '',
                    values: [
                        'accentColor' => '#333333',
                        'headingFont' => 'sora',
                    ],
                ),
            ],
        ),
        themeRenderer: $renderer,
        sectionRenderers: [],
    );

    app()->instance(ThemeTokenStore::class, new ThemeTokenStore(sys_get_temp_dir() . '/capell-theme-runtime-test'));

    $runtime = ResolveThemeRuntimeAction::run(
        activeTheme: 'child',
        activePreset: 'launch',
        brand: new BrandProfileData,
        themeOverrides: [
            'base' => ['primaryColor' => '#444444'],
            'child' => ['accentColor' => '#555555'],
        ],
    );

    expect($runtime->brand->primaryColor)->toBe('#444444')
        ->and($runtime->brand->accentColor)->toBe('#555555')
        ->and($runtime->brand->cardStyle)->toBe('bordered')
        ->and($runtime->brand->headingFont)->toBe('sora');
});
