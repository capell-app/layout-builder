<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('ships Laravel Boost guidelines for every package and skills only where useful', function (): void {
    $packagesWithSkills = [
        'address',
        'analytics',
        'assistant',
        'authentication-log',
        'backup',
        'blog',
        'campaigns',
        'content-blocks',
        'default-theme',
        'deployments',
        'developer-tools',
        'forms',
        'mcp',
        'media-curator',
        'mosaic',
        'navigation',
        'redirects',
        'seo-tools',
        'site-search',
        'tags',
        'theme-studio-admin',
        'theme-studio-core',
        'workspaces',
    ];

    $packageComposerFiles = Finder::create()
        ->files()
        ->in(dirname(__DIR__, 2) . '/packages')
        ->depth(1)
        ->name('composer.json')
        ->sortByName();

    foreach ($packageComposerFiles as $packageComposerFile) {
        $packagePath = $packageComposerFile->getPath();
        $packageName = basename($packagePath);
        $packageSkillFiles = glob($packagePath . '/resources/boost/skills/*/SKILL.md');
        $packageSkillFiles = $packageSkillFiles !== false ? $packageSkillFiles : [];

        expect($packagePath . '/resources/boost/guidelines/core.blade.php')->toBeFile();

        if (in_array($packageName, $packagesWithSkills, true)) {
            expect($packageSkillFiles)->not->toBeEmpty();

            continue;
        }

        expect($packageSkillFiles)->toBeEmpty();
    }
});
