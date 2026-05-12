<?php

declare(strict_types=1);

it('owns the opinionated public head behavior', function (): void {
    $component = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/app/head/custom.blade.php');
    $tokens = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/app/head/tokens.blade.php');

    expect($component)->toContain('localStorage.theme')
        ->and($component)->toContain('prefers-color-scheme: dark')
        ->and($component)->toContain('updateHeaderSticky')
        ->and($component)->toContain('<x-capell::app.head.tokens />')
        ->and($tokens)->toContain('--color-brand')
        ->and($tokens)->toContain('DefaultColorEnum::getKeyValues()')
        ->and($tokens)->toContain('->merge($theme->colors)')
        ->and($tokens)->toContain('$linkColorActiveMeta = $theme->getMeta(\'link_color_active\')')
        ->and($tokens)->toContain('$resolveColorToken($linkColorMeta, \'#1e40af\')')
        ->and($tokens)->toContain('ColorConverterAction::run($resolveColorToken($dividerColorMeta, \'#e5e7eb\'))');
});
