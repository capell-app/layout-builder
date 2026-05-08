<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('keeps demo-kit isolated from other packages', function (): void {
    $rootPath = dirname(__DIR__, 3);
    $packagesPath = $rootPath . '/packages';
    $forbiddenReferences = [
        'Capell\\DemoKit',
        'capell-app/demo-kit',
        'capell-demo-kit',
    ];
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($packagesPath)
        ->exclude('demo-kit')
        ->name('*.php')
        ->name('composer.json')
        ->name('capell.json');

    foreach ($files as $file) {
        $relativePath = str_replace($rootPath . '/', '', $file->getPathname());
        $contents = $file->getContents();

        foreach ($forbiddenReferences as $reference) {
            if (! str_contains($contents, $reference)) {
                continue;
            }

            $violations[] = sprintf('%s references %s', $relativePath, $reference);
        }
    }

    expect($violations)->toBe(
        [],
        'Only packages/demo-kit may reference DemoKit:' . PHP_EOL .
        json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});
