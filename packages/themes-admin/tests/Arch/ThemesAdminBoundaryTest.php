<?php

declare(strict_types=1);

arch('themes-admin does not import add-on packages')
    ->expect('Capell\Themes\Admin')
    ->not->toUse([
        'Capell\Address',
        'Capell\Assistant',
        'Capell\Blog',
        'Capell\Forms',
        'Capell\Media',
        'Capell\Mosaic',
        'Capell\Navigation',
        'Capell\Plugins',
        'Capell\SeoTools',
        'Capell\Tags',
        'Capell\Workspaces',
    ]);

arch()
    ->expect('Capell\Themes\Admin')
    ->classes()
    ->toUseStrictEquality();
