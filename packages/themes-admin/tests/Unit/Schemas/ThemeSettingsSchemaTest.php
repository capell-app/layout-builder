<?php

declare(strict_types=1);

use Capell\Themes\Admin\Schemas\ThemeSettingsSchema;
use Capell\Themes\Core\Theme\ThemeRegistrar;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;

beforeEach(function (): void {
    ThemeRegistrar::reset();
    ThemeRegistrar::register('corporate', 'Corporate');
    ThemeRegistrar::register('agency', 'Agency');
    ThemeRegistrar::register('saas', 'SaaS');
});

/**
 * Retrieve child components directly from the internal property, avoiding
 * the need for a Filament container (which requires the full Laravel app).
 */
function getThemeChildComponents(object $component): array
{
    $reflection = new ReflectionObject($component);

    if (! $reflection->hasProperty('childComponents')) {
        return [];
    }

    $prop = $reflection->getProperty('childComponents');
    $rawValue = $prop->getValue($component);

    if (! is_array($rawValue)) {
        return [];
    }

    $components = [];

    foreach ($rawValue as $group) {
        foreach ((array) $group as $child) {
            if (is_object($child)) {
                $components[] = $child;
            }
        }
    }

    return $components;
}

function flattenThemeComponents(object $component): array
{
    $result = [$component];

    foreach (getThemeChildComponents($component) as $child) {
        $result = array_merge($result, flattenThemeComponents($child));
    }

    return $result;
}

test('ThemeSettingsSchema::make() returns a Tabs instance', function (): void {
    $schema = ThemeSettingsSchema::make();

    expect($schema)->toBeInstanceOf(Tabs::class);
});

test('schema contains an active_theme Select', function (): void {
    $schema = ThemeSettingsSchema::make();
    $components = flattenThemeComponents($schema);

    $matches = array_filter(
        $components,
        fn (object $component): bool => $component instanceof Select && $component->getName() === 'active_theme',
    );

    expect($matches)->toHaveCount(1);
});

test('schema includes at least two ColorPickers', function (): void {
    $schema = ThemeSettingsSchema::make();
    $components = flattenThemeComponents($schema);

    $pickers = array_filter(
        $components,
        fn (object $component): bool => $component instanceof ColorPicker,
    );

    expect(count($pickers))->toBeGreaterThanOrEqual(2);
});
