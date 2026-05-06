<?php

declare(strict_types=1);

arch('tags does not import unrelated packages')
    ->expect('Capell\Tags')
    ->not->toUse([
        'Capell\Address',
        'Capell\AIOrchestrator',
        'Capell\Blog',
        'Capell\FormBuilder',
        'Capell\Media',
        'Capell\LayoutBuilder',
        'Capell\Marketplace',
        'Capell\SeoSuite',
        'Capell\Themes',
    ]);

arch()
    ->expect('Capell\Tags')
    ->classes()
    ->toUseStrictEquality();
