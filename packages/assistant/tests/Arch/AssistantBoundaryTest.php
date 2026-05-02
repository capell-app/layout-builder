<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

arch('assistant shared package surface does not import mosaic')
    ->expect('Capell\Assistant')
    ->not->toUse('Capell\Mosaic');

it('assistant source contains no direct mosaic references', function (): void {
    $packagePath = dirname(__DIR__, 2);
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($packagePath . '/src')
        ->name('*.php')
        ->contains('Capell\\Mosaic');

    foreach ($files as $file) {
        $violations[] = str_replace($packagePath . '/', '', $file->getPathname());
    }

    expect($violations)->toBeEmpty();
});

arch()
    ->expect('Capell\Assistant')
    ->classes()
    ->toUseStrictEquality();
