<?php

declare(strict_types=1);

use Capell\Admin\Console\Commands\DemoCommand;
use Capell\Admin\Console\Commands\InstallCommand;
use Capell\Core\Database\Factories\TypeFactory;
use Capell\Core\Support\Creator\DemoCreator;
use Capell\Frontend\Http\Middleware\ResolveFrontend;
use Saade\FilamentAdjacencyList\Forms\Components\Concerns\HasRelationship;

arch()
    ->expect('Capell\Blog')
    ->toOnlyBeUsedIn('Capell\Blog')
    ->ignoring([
        InstallCommand::class,
        DemoCommand::class,
        DemoCreator::class,
        TypeFactory::class,
    ]);

arch()
    ->preset()
    ->php()
    ->ignoring([
        'var_export',
    ]);

arch()
    ->preset()
    ->laravel()
    ->ignoring('exit');

arch()->preset()->security()
    ->ignoring([
        ResolveFrontend::class,
        HasRelationship::class,
    ]);

it('does not allow debug functions')
    ->expect(['dd', 'dump', 'print_r', 'die', 'ray', 'rd', 'var_dump'])
    ->toBeUsedInNothing();

arch()->expect(['env', 'sleep', 'usleep'])->toBeUsedInNothing()->ignoring([
    Capell\Blog\Console\Commands\InstallCommand::class,
]);

arch()
    ->expect([
        'Capell\Blog',
    ])
    ->classes()
    ->toUseStrictEquality();
