<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('does not depend on removed layout-builder package internals', function (): void {
    $rootPath = dirname(__DIR__, 4);
    $contentSectionsPath = $rootPath . '/packages/content-sections';
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($contentSectionsPath)
        ->exclude(['docs'])
        ->name(['*.php', '*.blade.php', '*.md', '*.json'])
        ->contains('Capell\\LayoutBuilder');

    foreach ($files as $file) {
        $violations[] = str_replace($rootPath . '/', '', $file->getPathname());
    }

    expect($violations)->toBeEmpty();
});
