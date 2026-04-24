<?php

declare(strict_types=1);

arch('seo-tools does not import packages that depend on it')
    ->expect('Capell\SeoTools')
    ->not->toUse([
        'Capell\Address',
        'Capell\Blog',
        'Capell\Forms',
        'Capell\Media',
        'Capell\Mosaic',
        'Capell\Navigation',
        'Capell\Plugins',
        'Capell\Tags',
        'Capell\Themes',
        'Capell\Workspaces',
    ]);

arch()
    ->expect('Capell\SeoTools')
    ->classes()
    ->toUseStrictEquality();
