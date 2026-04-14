<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Frontend\Http\Middleware\HtmlCacheMiddleware;
use Capell\Frontend\Support\Logging\FrontendLogger;
use Saade\FilamentAdjacencyList\Forms\Components\Concerns\HasRelationship;

arch()
    ->preset()
    ->php()
    ->ignoring([
        'var_export',
        FrontendLogger::class,
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
    ]);

arch()->expect(['env', 'sleep', 'usleep'])->toBeUsedInNothing();
