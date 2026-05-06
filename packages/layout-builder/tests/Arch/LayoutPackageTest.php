<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

arch('layout-builder does not import blog (blog depends on layout-builder, not the reverse)')
    ->expect('Capell\LayoutBuilder')
    ->not->toUse('Capell\Blog');

it('layout-builder source contains no direct blog references', function (): void {
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
    ->expect('Capell\LayoutBuilder')
    ->classes()
    ->toUseStrictEquality();
