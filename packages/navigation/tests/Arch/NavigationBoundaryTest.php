<?php

declare(strict_types=1);

arch('navigation does not import packages that depend on it')
    ->expect('Capell\Navigation')
    ->not->toUse([
        'Capell\Address',
        'Capell\Blog',
        'Capell\FormBuilder',
        'Capell\Media',
        'Capell\LayoutBuilder',
        'Capell\Marketplace',
        'Capell\SeoSuite',
        'Capell\Tags',
        'Capell\Themes',
        'Capell\PublishingStudio',
    ]);

arch()
    ->expect('Capell\Navigation')
    ->classes()
    ->toUseStrictEquality();
