<?php

declare(strict_types=1);

use Capell\Themes\Core\Mobile\TouchTargets;

test('classes returns correct Tailwind class string for default 44px', function (): void {
    $touch = new TouchTargets;

    expect($touch->classes())->toBe('min-h-[44px] min-w-[44px]');
});

test('inlineStyles returns correct CSS', function (): void {
    $touch = new TouchTargets;

    expect($touch->inlineStyles())->toBe('min-height:44px;min-width:44px;');
});

test('customClasses returns 48px variants', function (): void {
    $touch = new TouchTargets;

    expect($touch->customClasses(48))->toBe('min-h-[48px] min-w-[48px]');
});

test('minSize returns the int size', function (): void {
    $touch = new TouchTargets;

    expect($touch->minSize())->toBe(44);
});

test('asAttributes returns style attribute string', function (): void {
    $touch = new TouchTargets;

    expect($touch->asAttributes())->toBe('style="min-height:44px;min-width:44px;"');
});
