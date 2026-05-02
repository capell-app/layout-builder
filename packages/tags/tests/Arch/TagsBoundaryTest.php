<?php

declare(strict_types=1);

arch('tags does not import unrelated packages')
    ->expect('Capell\Tags')
    ->not->toUse([
        'Capell\Address',
        'Capell\Assistant',
        'Capell\Blog',
        'Capell\Forms',
        'Capell\Media',
        'Capell\Mosaic',
        'Capell\Plugins',
        'Capell\SeoTools',
        'Capell\Themes',
    ]);

arch()
    ->expect('Capell\Tags')
    ->classes()
    ->toUseStrictEquality();
