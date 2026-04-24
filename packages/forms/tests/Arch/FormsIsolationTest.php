<?php

declare(strict_types=1);

arch()
    ->expect('Capell\Forms')
    ->toOnlyBeUsedIn('Capell\Forms');

arch()
    ->expect('Capell\Forms')
    ->classes()
    ->toUseStrictEquality();
