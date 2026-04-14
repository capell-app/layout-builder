<?php

declare(strict_types=1);

use Capell\Admin\Console\Commands\DemoCommand;
use Capell\Admin\Console\Commands\InstallCommand;
use Capell\Core\Database\Factories\TypeFactory;
use Capell\Core\Support\Creator\DemoCreator;

arch()
    ->expect('Capell\Blog')
    ->toOnlyBeUsedIn('Capell\Blog')
    ->ignoring([
        InstallCommand::class,
        DemoCommand::class,
        DemoCreator::class,
        TypeFactory::class,
        Capell\Hero\Console\Commands\DemoCommand::class,
    ]);

arch()
    ->expect([
        'Capell\Blog',
    ])
    ->classes()
    ->toUseStrictEquality();
