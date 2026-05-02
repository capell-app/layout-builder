<?php

declare(strict_types=1);

use Capell\DefaultTheme\View\Components\Media\Svg;

require_once dirname(__DIR__, 2) . '/src/View/Components/Media/Svg.php';

function makeDefaultThemeSvgComponent(string $contents): Svg
{
    $temporaryPath = tempnam(sys_get_temp_dir(), 'capell-svg-');

    expect($temporaryPath)->toBeString();

    file_put_contents($temporaryPath, $contents);

    try {
        return new Svg($temporaryPath);
    } finally {
        unlink($temporaryPath);
    }
}

test('svg sanitizer removes remote hrefs while keeping local references', function (): void {
    $component = makeDefaultThemeSvgComponent(<<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20">
    <defs>
        <linearGradient id="localGradient"></linearGradient>
    </defs>
    <use href="#localGradient" />
    <image href="https://example.com/remote.svg" />
    <image xlink:href="http://example.com/remote.svg" xmlns:xlink="http://www.w3.org/1999/xlink" />
    <image href="/images/local.svg#icon" />
</svg>
SVG);

    expect($component->contents)
        ->toContain('href="#localGradient"')
        ->toContain('href="/images/local.svg#icon"')
        ->not->toContain('https://example.com')
        ->not->toContain('http://example.com');
});

test('svg sanitizer strips style tags and unsafe css urls', function (): void {
    $component = makeDefaultThemeSvgComponent(<<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20">
    <style>
        rect { fill: url(https://example.com/paint.svg); }
    </style>
    <rect style="fill: url(https://example.com/paint.svg)" width="20" height="20" />
    <circle style="fill: url(#localGradient)" />
    <path href="javascript:alert(1)" />
</svg>
SVG);

    expect($component->contents)
        ->not->toContain('<style')
        ->not->toContain('https://example.com')
        ->not->toContain('javascript:')
        ->not->toContain('<rect style=')
        ->toContain('style="fill: url(#localGradient)"');
});
