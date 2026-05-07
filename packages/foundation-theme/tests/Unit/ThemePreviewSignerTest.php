<?php

declare(strict_types=1);

use Capell\ThemeStudio\Core\Preview\ThemePreviewSigner;

it('creates a preview context from a signed theme token', function (): void {
    $signer = new ThemePreviewSigner(secretKey: 'theme-secret');
    $token = $signer->generate(themeKey: 'agency', presetKey: 'signal');
    $context = $signer->contextFromToken($token);

    expect($context->previewing)->toBeTrue()
        ->and($context->themeKey)->toBe('agency')
        ->and($context->presetKey)->toBe('signal');
});

it('rejects tampered preview tokens', function (): void {
    $signer = new ThemePreviewSigner(secretKey: 'theme-secret');
    $token = $signer->generate(themeKey: 'agency', presetKey: 'signal') . 'tampered';

    expect($signer->contextFromToken($token)->previewing)->toBeFalse();
});
