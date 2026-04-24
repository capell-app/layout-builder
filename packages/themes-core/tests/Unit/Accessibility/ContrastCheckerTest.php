<?php

declare(strict_types=1);

use Capell\Themes\Core\Accessibility\ContrastChecker;

test('black on white gives ratio close to 21', function (): void {
    $checker = new ContrastChecker;
    $ratio = $checker->ratio('#000000', '#ffffff');

    expect($ratio)->toBeGreaterThan(20.9);
    expect($ratio)->toBeLessThan(21.1);
});

test('white on white gives ratio of 1', function (): void {
    $checker = new ContrastChecker;
    $ratio = $checker->ratio('#ffffff', '#ffffff');

    expect($ratio)->toBe(1.0);
});

test('dark blue on white meets AA', function (): void {
    $checker = new ContrastChecker;
    $ratio = $checker->ratio('#1a2d6d', '#ffffff');

    expect($checker->meetsAA($ratio))->toBeTrue();
});

test('two similar mid-greys do not meet AA', function (): void {
    $checker = new ContrastChecker;
    $ratio = $checker->ratio('#888888', '#999999');

    expect($checker->meetsAA($ratio))->toBeFalse();
});

test('meetsAAA with ratio 8 is true and ratio 6 is false', function (): void {
    $checker = new ContrastChecker;

    expect($checker->meetsAAA(8.0))->toBeTrue();
    expect($checker->meetsAAA(6.0))->toBeFalse();
});
