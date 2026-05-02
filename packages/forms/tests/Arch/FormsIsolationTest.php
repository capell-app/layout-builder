<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('keeps forms package references inside the forms source package', function (): void {
    $rootPath = dirname(__DIR__, 4);
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($rootPath . '/packages')
        ->path('/\/src\//')
        ->name('*.php')
        ->contains('Capell\\Forms');

    foreach ($files as $file) {
        $relativePath = str_replace($rootPath . '/', '', $file->getPathname());

        if (str_starts_with($relativePath, 'packages/forms/src/')) {
            continue;
        }

        $violations[] = $relativePath;
    }

    expect($violations)->toBeEmpty();
});

arch()
    ->expect('Capell\Forms')
    ->classes()
    ->toUseStrictEquality();
