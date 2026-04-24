<?php

declare(strict_types=1);

use Capell\Admin\Console\Commands\DemoCommand;
use Capell\Admin\Console\Commands\InstallCommand;
use Capell\Admin\Filament\Pages\SystemHealthPage;
use Capell\Core\Database\Factories\TypeFactory;
use Capell\Core\Support\Creator\DemoCreator;
use Capell\Workspaces\Providers\WorkspacesServiceProvider;

arch()
    ->expect('Capell\Blog')
    ->toOnlyBeUsedIn('Capell\Blog')
    ->ignoring([
        InstallCommand::class,
        DemoCommand::class,
        DemoCreator::class,
        TypeFactory::class,
        Capell\Mosaic\Console\Commands\Hero\DemoCommand::class,
        SystemHealthPage::class,
        WorkspacesServiceProvider::class,
    ]);

arch()
    ->expect('Capell\Blog')
    ->classes()
    ->toUseStrictEquality();
