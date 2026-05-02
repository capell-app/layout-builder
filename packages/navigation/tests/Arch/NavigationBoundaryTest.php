<?php

declare(strict_types=1);

arch('navigation does not import packages that depend on it')
    ->expect('Capell\Navigation')
    ->not->toUse([
        'Capell\Address',
        'Capell\Blog',
        'Capell\Forms',
        'Capell\Media',
        'Capell\Mosaic',
        'Capell\Plugins',
        'Capell\SeoTools',
        'Capell\Tags',
        'Capell\Themes',
        'Capell\Workspaces',
    ]);

arch()
    ->expect('Capell\Navigation')
    ->classes()
    ->toUseStrictEquality();
