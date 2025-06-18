<?php

declare(strict_types=1);

arch('Layout package to be standalone')
    ->expect('Capell\Layout')
    ->not->toUse(['Capell\Blog']);
