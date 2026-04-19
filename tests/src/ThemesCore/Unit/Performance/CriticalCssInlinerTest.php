<?php

declare(strict_types=1);

use Capell\Themes\Core\Performance\CriticalCssInliner;

test('wraps css in a style tag and minifies whitespace', function (): void {
    $inliner = new CriticalCssInliner;
    $css = "/* hello */\n.button {\n  color: red;\n}\n";

    $html = $inliner->fromString($css);

    expect($html)
        ->toStartWith('<style data-capell-critical>')
        ->toContain('.button{color:red}')
        ->not->toContain('/* hello */');
});

test('returns empty string for missing file', function (): void {
    $inliner = new CriticalCssInliner;

    expect($inliner->fromFile('/nope/missing.css'))->toBe('');
});
