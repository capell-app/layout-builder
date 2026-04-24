<?php

declare(strict_types=1);

arch('themes-core does not import packages that depend on it')
    ->expect('Capell\Themes\Core')
    ->not->toUse([
        'Capell\Address',
        'Capell\Assistant',
        'Capell\Blog',
        'Capell\Forms',
        'Capell\Media',
        'Capell\Mosaic',
        'Capell\Navigation',
        'Capell\Plugins',
        'Capell\Tags',
        'Capell\Themes\Admin',
        'Capell\Workspaces',
    ]);

arch()
    ->expect('Capell\Themes\Core')
    ->classes()
    ->toUseStrictEquality();
