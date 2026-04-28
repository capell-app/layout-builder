<?php

declare(strict_types=1);

arch()
    ->expect('Capell\Plugins')
    ->toOnlyBeUsedIn('Capell\Plugins');

arch()
    ->expect('Capell\Plugins')
    ->classes()
    ->toUseStrictEquality();
