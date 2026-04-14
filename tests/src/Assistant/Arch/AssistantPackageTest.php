<?php

declare(strict_types=1);

arch()
    ->expect('Capell\\Assistant')
    ->toOnlyBeUsedIn('Capell\\Assistant');

arch()
    ->expect([
        'Capell\Hero',
    ])
    ->classes()
    ->toUseStrictEquality();
