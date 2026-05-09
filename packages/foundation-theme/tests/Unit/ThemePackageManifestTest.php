<?php

declare(strict_types=1);

it('declares foundation as the default theme package', function (): void {
    $manifest = themePackageManifest('foundation-theme');
    $composer = themePackageComposer('foundation-theme');

    expect($manifest['name'])->toBe('capell-app/foundation-theme')
        ->and($composer['name'])->toBe('capell-app/foundation-theme')
        ->and($manifest['manifest-version'])->toBe(2)
        ->and($manifest['kind'])->toBe('theme')
        ->and($manifest['themeKey'])->toBe('default')
        ->and($manifest['extends'])->toBeNull();
});

it('declares premium themes as standalone packages extending foundation', function (string $packageDirectory, string $composerName, string $themeKey): void {
    $manifest = themePackageManifest($packageDirectory);
    $composer = themePackageComposer($packageDirectory);

    expect($manifest['name'])->toBe($composerName)
        ->and($composer['name'])->toBe($composerName)
        ->and($manifest['kind'])->toBe('theme')
        ->and($manifest['themeKey'])->toBe($themeKey)
        ->and($manifest['extends'])->toBe('capell-app/foundation-theme')
        ->and($manifest['dependencies']['requires'])->toContain('capell-app/foundation-theme')
        ->and($manifest['productGroup'])->toBe('Capell Themes');
})->with([
    'agency' => ['theme-agency', 'capell-app/theme-agency', 'agency'],
    'corporate' => ['theme-corporate', 'capell-app/theme-corporate', 'corporate'],
    'saas' => ['theme-saas', 'capell-app/theme-saas', 'saas'],
]);

/**
 * @return array<string, mixed>
 */
function themePackageManifest(string $packageDirectory): array
{
    return json_decode(
        (string) file_get_contents(dirname(__DIR__, 3) . '/' . $packageDirectory . '/capell.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/**
 * @return array<string, mixed>
 */
function themePackageComposer(string $packageDirectory): array
{
    return json_decode(
        (string) file_get_contents(dirname(__DIR__, 3) . '/' . $packageDirectory . '/composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}
