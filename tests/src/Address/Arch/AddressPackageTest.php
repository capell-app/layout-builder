<?php

declare(strict_types=1);

arch()
    ->expect('Capell\\Address')
    ->toOnlyBeUsedIn('Capell\\Address');

arch()
    ->expect('Capell\\Address')
    ->classes()
    ->toUseStrictEquality();
