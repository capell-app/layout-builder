<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Http\Middleware\HtmlCacheMiddleware;
use Capell\Frontend\Livewire\Page\SitemapPage;

arch()
    ->expect('Capell\Blog')
    ->toOnlyBeUsedIn('Capell\Blog')
    ->ignoring([
        Capell\Admin\Commands\InstallCommand::class,
        Capell\Admin\Commands\DemoCommand::class,
        Capell\Admin\Services\Creator\DemoCreator::class,
        Capell\Core\Database\Factories\TypeFactory::class,
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

arch()->preset()->security();

it('does not allow debug functions')
    ->expect(['dd', 'dump', 'print_r', 'die', 'ray', 'rd', 'var_dump'])
    ->toBeUsedInNothing()
    ->ignoring([
        Frontend::class,
        EditPage::class,
    ]);

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
