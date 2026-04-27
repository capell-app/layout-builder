<?php

declare(strict_types=1);

use Capell\Core\Console\Commands\DoctorCommand;
use Capell\Core\Models\Page;
use Capell\Core\Observers\PageUrlObserver;
use Capell\Core\Support\Upgrade\EnsureMorphMapUpgradeStep;

arch('core does not reference Capell\\Workspaces namespace')
    ->expect('Capell\Core')
    ->not->toUse('Capell\Workspaces')
    ->ignoring([
        // Exchanger (core sub-module) works with workspace data directly
        'Capell\Core\Exchanger',
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
        'Capell\Forms',
        'Capell\SeoTools',
        'Capell\Themes',
    ]);

arch()
    ->expect('Capell\Workspaces')
    ->classes()
    ->toUseStrictEquality();
