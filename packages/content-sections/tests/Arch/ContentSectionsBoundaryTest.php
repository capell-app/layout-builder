<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('is not referenced by the layout-builder package', function (): void {
    $rootPath = dirname(__DIR__, 4);
    $layoutBuilderPath = $rootPath . '/packages/layout-builder';
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($layoutBuilderPath)
        ->exclude(['docs'])
        ->name(['*.php', '*.blade.php', '*.md', '*.json'])
        ->contains('Capell\\ContentSections');

    foreach ($files as $file) {
        $violations[] = str_replace($rootPath . '/', '', $file->getPathname());
    }

    expect($violations)->toBeEmpty();
});
