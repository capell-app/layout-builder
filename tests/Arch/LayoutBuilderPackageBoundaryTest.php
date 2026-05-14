<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('only uses legacy core layout builder classes at approved bridge points', function (): void {
    $sourcePath = dirname(__DIR__, 2) . '/src';

    $legacyReferences = collect((new Finder)->files()->in($sourcePath)->name('*.php'))
        ->mapWithKeys(function (SplFileInfo $file): array {
            $relativePath = str_replace(dirname(__DIR__, 2) . '/', '', $file->getRealPath() ?: $file->getPathname());

            return [$relativePath => file_get_contents($file->getRealPath() ?: $file->getPathname())];
        })
        ->filter(fn (string $contents): bool => str_contains($contents, 'Capell\\Core\\LayoutBuilder\\'))
        ->keys()
        ->sort()
        ->values()
        ->all();

    expect($legacyReferences)->toBe([
        'src/Support/DefaultPublicWidgetPayloadResolver.php',
    ]);
});
