<?php

declare(strict_types=1);

use Capell\Themes\Saas\SaasThemeServiceProvider;

test('provider exposes widget list', function () {
    $widgets = SaasThemeServiceProvider::widgets();

    expect($widgets)->toBeArray()->toHaveCount(9);
    foreach ($widgets as $class) {
        expect(class_exists($class))->toBeTrue();
    }
});

test('theme key constant is saas', function () {
    expect(SaasThemeServiceProvider::THEME_KEY)->toBe('saas');
});

test('provider declares version', function () {
    expect(SaasThemeServiceProvider::VERSION)->toBeString()->not->toBeEmpty();
});
