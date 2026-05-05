<?php

declare(strict_types=1);

use Capell\Core\Console\Commands\DoctorCommand;
use Capell\Core\Models\Page;
use Capell\Core\Observers\PageUrlObserver;
use Capell\Core\Support\Upgrade\EnsureMorphMapUpgradeStep;
use Symfony\Component\Finder\Finder;

arch('core does not reference Capell\\Workspaces namespace')
    ->expect('Capell\Core')
    ->not->toUse('Capell\Workspaces')
    ->ignoring([
        // Migrator import/export workflows work with workspace data directly.
        'Capell\Migrator',
        // Page model uses the BelongsToWorkspace trait
        Page::class,
        // PageUrlObserver needs WorkspaceContextScope for draft-aware URL queries
        PageUrlObserver::class,
        // Upgrade step and doctor command inspect workspace registry at runtime
        EnsureMorphMapUpgradeStep::class,
        DoctorCommand::class,
    ]);

arch('workspaces does not import unrelated packages')
    ->expect('Capell\Workspaces')
    ->not->toUse([
        'Capell\Address',
        'Capell\Assistant',
        'Capell\Blog',
        'Capell\Forms',
        'Capell\Mosaic',
        'Capell\Navigation',
        'Capell\SeoTools',
        'Capell\Themes',
    ]);

it('workspaces source contains no direct plugin package references', function (): void {
    $packagePath = dirname(__DIR__, 2);
    $forbiddenNamespaces = [
        'Capell\\Blog',
        'Capell\\Mosaic',
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
    ->expect('Capell\Workspaces')
    ->classes()
    ->toUseStrictEquality();
