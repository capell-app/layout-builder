<?php

declare(strict_types=1);

arch()
    ->expect('Capell\\Hero')
    ->toOnlyBeUsedIn('Capell\\Hero');

arch()
    ->expect([
        'Capell\Hero',
    ])
    ->classes()
    ->toUseStrictEquality();
