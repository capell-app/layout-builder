<?php

declare(strict_types=1);

use Capell\Core\Console\Commands\DoctorCommand;
use Capell\Core\Models\Page;
use Capell\Core\Observers\PageUrlObserver;
use Capell\Core\Support\Upgrade\EnsureMorphMapUpgradeStep;
use Symfony\Component\Finder\Finder;

arch('core does not reference Capell\\PublishingStudio namespace')
    ->expect('Capell\Core')
    ->not->toUse('Capell\PublishingStudio')
    ->ignoring([
        // MigrationAssistant import/export workflows work with workspace data directly.
        'Capell\MigrationAssistant',
        // Page model uses the BelongsToWorkspace trait
        Page::class,
        // PageUrlObserver needs WorkspaceContextScope for draft-aware URL queries
        PageUrlObserver::class,
        // Upgrade step and doctor command inspect workspace registry at runtime
        EnsureMorphMapUpgradeStep::class,
        DoctorCommand::class,
    ]);

arch('publishing-studio does not import unrelated packages')
    ->expect('Capell\PublishingStudio')
    ->not->toUse([
        'Capell\Address',
        'Capell\AIOrchestrator',
        'Capell\Blog',
        'Capell\FormBuilder',
        'Capell\LayoutBuilder',
        'Capell\Navigation',
        'Capell\SeoSuite',
        'Capell\Themes',
    ]);

it('publishing-studio source contains no direct plugin package references', function (): void {
    $packagePath = dirname(__DIR__, 2);
    $forbiddenNamespaces = [
        'Capell\\Blog',
        'Capell\\LayoutBuilder',
        'Capell\\Navigation',
    ];
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($packagePath . '/src')
        ->name('*.php');

    foreach ($files as $file) {
        $relativePath = str_replace($packagePath . '/', '', $file->getPathname());
        $contents = $file->getContents();

        foreach ($forbiddenNamespaces as $namespace) {
            if (! str_contains($contents, $namespace)) {
                continue;
            }

            $violations[] = sprintf('%s references %s', $relativePath, $namespace);
        }
    }

    expect($violations)->toBeEmpty();
});

arch()
    ->expect('Capell\PublishingStudio')
    ->classes()
    ->toUseStrictEquality();
