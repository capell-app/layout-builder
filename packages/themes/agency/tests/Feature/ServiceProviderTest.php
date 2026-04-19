<?php

declare(strict_types=1);

use Capell\Themes\Agency\AgencyThemeServiceProvider;

test('provider exposes widget list', function () {
    $widgets = AgencyThemeServiceProvider::widgets();

    expect($widgets)->toBeArray()->toHaveCount(9);
    foreach ($widgets as $class) {
        expect(class_exists($class))->toBeTrue();
    }
});

test('theme key constant is agency', function () {
    expect(AgencyThemeServiceProvider::THEME_KEY)->toBe('agency');
});
