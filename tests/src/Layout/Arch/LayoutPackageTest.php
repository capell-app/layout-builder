<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Frontend\Http\Middleware\HtmlCacheMiddleware;
use Capell\Frontend\Livewire\Page\SitemapPage;
use Saade\FilamentAdjacencyList\Forms\Components\Concerns\HasRelationship;

arch('Layout package to be standalone')
    ->expect('Capell\Layout')
    ->not->toUse(['Capell\Blog']);

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

arch()
    ->preset()
    ->security()
    ->ignoring([
        HasRelationship::class,
    ]);

it('does not allow debug functions')
    ->expect(['dd', 'dump', 'print_r', 'die', 'ray', 'rd', 'var_dump'])
    ->toBeUsedInNothing()
    ->ignoring([
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
        'Capell\Layout',
    ])
    ->classes()
    ->toUseStrictEquality();
