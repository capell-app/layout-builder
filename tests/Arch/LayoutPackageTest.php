<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

arch('mosaic does not import blog (blog depends on mosaic, not the reverse)')
    ->expect('Capell\Mosaic')
    ->not->toUse('Capell\Blog');

it('mosaic source contains no direct blog references', function (): void {
    $packagePath = dirname(__DIR__, 2);
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($packagePath . '/src')
        ->name('*.php')
        ->contains('Capell\\Blog');

    foreach ($files as $file) {
        $violations[] = str_replace($packagePath . '/', '', $file->getPathname());
    }

    expect($violations)->toBeEmpty();
});

arch()
    ->expect('Capell\Mosaic')
    ->classes()
    ->toUseStrictEquality();
