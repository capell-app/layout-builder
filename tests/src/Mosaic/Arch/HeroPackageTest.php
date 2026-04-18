<?php

declare(strict_types=1);

namespace Capell\Tests\Mosaic\Hero\Arch;

arch()
    ->expect('Capell\\Hero')
    ->toOnlyBeUsedIn('Capell\\Hero');

arch()
    ->expect([
        'Capell\Hero',
    ])
    ->classes()
    ->toUseStrictEquality();
