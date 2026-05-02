<?php

declare(strict_types=1);

use Capell\Core\Models\Theme;
use Capell\Core\Support\Tailwind\TailwindAssetsRegistry;
use Capell\DefaultTheme\Support\Tailwind\TailwindAssetsGenerator;
use Illuminate\Filesystem\Filesystem;

require_once dirname(__DIR__, 2) . '/src/Support/Tailwind/TailwindAssetsGenerator.php';

function invokeDefaultThemeTailwindGeneratorMethod(
    TailwindAssetsGenerator $generator,
    string $methodName,
    array $parameters = [],
): mixed {
    $reflectionMethod = new ReflectionMethod($generator, $methodName);

    return $reflectionMethod->invokeArgs($generator, $parameters);
}

test('theme output css paths outside the base target directory are ignored', function (): void {
    $generator = new TailwindAssetsGenerator(new Filesystem);
    $theme = new Theme;
    $theme->key = 'dark';
    $theme->meta = ['output_css' => '/tmp/pwn.css'];

    $path = invokeDefaultThemeTailwindGeneratorMethod(
        $generator,
        'themeOutputPath',
        [$theme, '/var/www/app/resources/css/capell/frontend.css'],
    );

    expect($path)->toBe('/var/www/app/resources/css/capell/frontend-dark.css');
});

test('theme output css paths must keep a css extension', function (): void {
    $generator = new TailwindAssetsGenerator(new Filesystem);
    $theme = new Theme;
    $theme->key = 'dark';
    $theme->meta = ['output_css' => '/var/www/app/resources/css/capell/dark.txt'];

    $path = invokeDefaultThemeTailwindGeneratorMethod(
        $generator,
        'themeOutputPath',
        [$theme, '/var/www/app/resources/css/capell/frontend.css'],
    );

    expect($path)->toBe('/var/www/app/resources/css/capell/frontend-dark.css');
});

test('theme output css paths inside the base target directory are allowed', function (): void {
    $generator = new TailwindAssetsGenerator(new Filesystem);
    $theme = new Theme;
    $theme->key = 'dark';
    $theme->meta = ['output_css' => '/var/www/app/resources/css/capell/custom-dark.css'];

    $path = invokeDefaultThemeTailwindGeneratorMethod(
        $generator,
        'themeOutputPath',
        [$theme, '/var/www/app/resources/css/capell/frontend.css'],
    );

    expect($path)->toBe('/var/www/app/resources/css/capell/custom-dark.css');
});

test('theme color keys and values are validated before registration', function (): void {
    $generator = new TailwindAssetsGenerator(new Filesystem);
    $registry = new TailwindAssetsRegistry;
    $theme = new Theme;
    $theme->key = 'brand';
    $theme->meta = [
        'colors' => [
            'primary' => '#123abc',
            'accent-600' => 'rgb(12 34 56 / 50%)',
            'bad;color' => '#ffffff',
            'remote' => 'url(https://example.com/color.svg)',
            'injected' => 'red; background: black',
            'badHex' => '#12345',
        ],
    ];

    invokeDefaultThemeTailwindGeneratorMethod($generator, 'registerThemeColorsFromTheme', [$registry, $theme]);

    expect($registry->themeColors()->all())->toBe([
        'accent-600' => 'rgb(12 34 56 / 50%)',
        'primary' => '#123abc',
    ]);
});

test('invalid provider-registered theme colors are skipped during render', function (): void {
    $generator = new TailwindAssetsGenerator(new Filesystem);
    $registry = new TailwindAssetsRegistry;
    $registry->registerThemeColor('primary', '#ffffff');
    $registry->registerThemeColor('bad;color', '#000000');
    $registry->registerThemeColor('remote', 'url(https://example.com/color.svg)');
    $registry->registerThemeColor('injected', 'red; background: black');

    $css = invokeDefaultThemeTailwindGeneratorMethod($generator, 'renderCss', [$registry]);

    expect($css)
        ->toContain('--color-primary: #ffffff;')
        ->not->toContain('bad;color')
        ->not->toContain('remote')
        ->not->toContain('injected')
        ->not->toContain('url(');
});
