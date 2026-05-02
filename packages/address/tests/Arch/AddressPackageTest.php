<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('keeps address package references inside the address source package', function (): void {
    $rootPath = dirname(__DIR__, 4);
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($rootPath . '/packages')
        ->path('/\/src\//')
        ->name('*.php')
        ->contains('Capell\\Address');

    foreach ($files as $file) {
        $relativePath = str_replace($rootPath . '/', '', $file->getPathname());

        if (str_starts_with($relativePath, 'packages/address/src/')) {
            continue;
        }

        $violations[] = $relativePath;
    }

    expect($violations)->toBeEmpty();
});

arch()
    ->expect('Capell\\Address')
    ->classes()
    ->toUseStrictEquality();
