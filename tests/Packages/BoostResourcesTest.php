<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('ships Laravel Boost guidelines for every package and skills only where useful', function (): void {
    $packagesWithSkills = [
        'address',
        'insights',
        'ai-orchestrator',
        'login-audit',
        'backup',
        'blog',
        'campaign-studio',
        'foundation-theme',
        'deployments',
        'diagnostics',
        'form-builder',
        'agent-bridge',
        'media-library',
        'layout-builder',
        'navigation',
        'redirects',
        'seo-suite',
        'search',
        'tags',
        'publishing-studio',
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
