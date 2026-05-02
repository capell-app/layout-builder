<?php

declare(strict_types=1);

use Capell\ThemeStudio\Core\Actions\ResolveBrandProfileAction;
use Capell\ThemeStudio\Core\Assets\ThemeAssetKey;
use Capell\ThemeStudio\Core\Assets\ThemeTokenRenderer;
use Capell\ThemeStudio\Core\Data\BrandProfileData;
use Capell\ThemeStudio\Core\Data\ThemeDefinitionData;
use Capell\ThemeStudio\Core\Data\ThemeOverrideData;
use Capell\ThemeStudio\Core\Data\ThemePresetData;

it('merges global brand, preset values, and theme overrides in order', function (): void {
    $brand = new BrandProfileData(primaryColor: '#111111', accentColor: '#222222', cardStyle: 'subtle');
    $definition = new ThemeDefinitionData(
        key: 'corporate',
        name: 'Corporate',
        description: 'Trust-led',
        package: 'capell-app/theme-corporate',
        previewImage: '/preview.jpg',
        tags: ['Trust'],
        bestFit: ['B2B'],
        includedSections: ['hero'],
        presets: [
            new ThemePresetData(
                key: 'boardroom',
                name: 'Boardroom',
                description: 'Formal',
                previewImage: '/preview.jpg',
                values: ['primaryColor' => '#333333', 'cardStyle' => 'bordered'],
            ),
        ],
    );

    $resolved = ResolveBrandProfileAction::run(
        brand: $brand,
        definition: $definition,
        override: new ThemeOverrideData(
            themeKey: 'corporate',
            presetKey: 'boardroom',
            values: ['accentColor' => '#444444'],
        ),
    );

    expect($resolved->primaryColor)->toBe('#333333')
        ->and($resolved->accentColor)->toBe('#444444')
        ->and($resolved->cardStyle)->toBe('bordered');
});

it('renders css tokens and isolated asset keys from a resolved brand profile', function (): void {
    $brand = new BrandProfileData(primaryColor: '#123456', accentColor: '#abcdef');
    $css = (new ThemeTokenRenderer)->css($brand);

    expect($css)->toContain('--theme-primary: #123456;')
        ->and($css)->toContain('--theme-accent: #abcdef;')
        ->and(ThemeAssetKey::make('corporate', 'boardroom', $brand))->toStartWith('theme-studio:corporate:boardroom:');
});
