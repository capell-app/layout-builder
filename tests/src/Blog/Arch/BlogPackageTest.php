<?php

declare(strict_types=1);

use Capell\Admin\Commands\DemoCommand;
use Capell\Admin\Commands\InstallCommand;
use Capell\Admin\Services\Creator\DemoCreator;
use Capell\Core\Database\Factories\TypeFactory;
use Capell\Frontend\Http\Middleware\HtmlCacheMiddleware;
use Capell\Frontend\Livewire\Page\SitemapPage;
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
        HasRelationship::class,
    ]);

it('does not allow debug functions')
    ->expect(['dd', 'dump', 'print_r', 'die', 'ray', 'rd', 'var_dump'])
    ->toBeUsedInNothing();

it('does not use exit functions')
    ->expect(['exit'])
    ->toBeUsedInNothing()
    ->ignoring([
        HtmlCacheMiddleware::class,
        SitemapPage::class,
    ]);

arch()->expect(['env', 'sleep', 'usleep'])->toBeUsedInNothing();

arch()
    ->expect([
        'Capell\Blog',
    ])
    ->classes()
    ->toUseStrictEquality();
