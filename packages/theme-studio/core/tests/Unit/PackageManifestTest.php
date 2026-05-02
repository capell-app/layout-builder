<?php

declare(strict_types=1);

it('uses the reset Theme Studio package names', function (string $path, string $expectedName): void {
    $manifest = json_decode((string) file_get_contents($path . '/capell.json'), true, flags: JSON_THROW_ON_ERROR);
    $composer = json_decode((string) file_get_contents($path . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['name'])->toBe($expectedName)
        ->and($composer['name'])->toBe($expectedName)
        ->and($manifest['productGroup'])->toBe('Capell Theme Studio')
        ->and($manifest['tier'])->toBe('premium');
})->with([
    'meta package' => [dirname(__DIR__, 3) . '/theme-studio', 'capell-app/theme-studio'],
    'core package' => [dirname(__DIR__, 2), 'capell-app/theme-studio-core'],
    'admin package' => [dirname(__DIR__, 3) . '/admin', 'capell-app/theme-studio-admin'],
    'corporate theme' => [dirname(__DIR__, 3) . '/themes/corporate', 'capell-app/theme-corporate'],
    'agency theme' => [dirname(__DIR__, 3) . '/themes/agency', 'capell-app/theme-agency'],
    'saas theme' => [dirname(__DIR__, 3) . '/themes/saas', 'capell-app/theme-saas'],
]);
