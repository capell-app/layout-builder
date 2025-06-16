<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\PageResource\Pages\EditPage;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Http\Middleware\HtmlCacheMiddleware;
use Capell\Frontend\Livewire\Page\SitemapPage;

arch()->preset()->php();

arch()
    ->preset()
    ->laravel()
    ->ignoring('exit');

arch()->preset()->security()
    ->ignoring([
        Capell\Core\Commands\InstallCommand::class,
    ]);

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
        'Capell\Admin',
        'Capell\Frontend',
        'Capell\Core',
    ])
    ->classes()
    ->toUseStrictEquality()
    /*->toHavePropertiesDocumented()
    ->toHaveMethodsDocumented()*/;
