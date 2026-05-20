<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('does not use retired core layout builder namespaces', function (): void {
    $sourcePath = dirname(__DIR__, 2) . '/src';

    $legacyReferences = collect((new Finder)->files()->in($sourcePath)->name('*.php'))
        ->mapWithKeys(function (SplFileInfo $file): array {
            $filePath = $file->getRealPath() !== false ? $file->getRealPath() : $file->getPathname();
            $relativePath = str_replace(dirname(__DIR__, 2) . '/', '', $filePath);

            return [$relativePath => file_get_contents($filePath)];
        })
        ->filter(fn (string $contents): bool => str_contains($contents, 'Capell\\Core\\LayoutBuilder\\'))
        ->keys()
        ->sort()
        ->values()
        ->all();

    expect($legacyReferences)->toBe([]);
});

it('keeps companion package source off legacy layout builder namespaces', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $legacyReferences = collect((new Finder)->files()->in($packagesPath)->exclude('layout-builder')->name(['*.php', '*.blade.php']))
        ->filter(fn (SplFileInfo $file): bool => str_contains($file->getPathname(), '/src/')
            || str_contains($file->getPathname(), '/resources/'))
        ->mapWithKeys(function (SplFileInfo $file) use ($packagesPath): array {
            $filePath = $file->getRealPath() !== false ? $file->getRealPath() : $file->getPathname();
            $relativePath = str_replace($packagesPath . '/', '', $filePath);

            return [$relativePath => file_get_contents($filePath)];
        })
        ->filter(fn (string $contents): bool => str_contains($contents, 'Capell\\Core\\LayoutBuilder\\')
            || str_contains($contents, 'Capell\\Admin\\LayoutBuilder\\'))
        ->keys()
        ->sort()
        ->values()
        ->all();

    expect($legacyReferences)->toBe([]);
});
