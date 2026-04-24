<?php

declare(strict_types=1);

use Capell\Mosaic\Console\Commands\Hero\DemoCommand;

arch('mosaic does not import blog (blog depends on mosaic, not the reverse)')
    ->expect('Capell\Mosaic')
    ->not->toUse('Capell\Blog')
    ->ignoring([
        // DemoCommand seeds demo blog content — acceptable for a dev-only command
        DemoCommand::class,
    ]);

arch()
    ->expect('Capell\Mosaic')
    ->classes()
    ->toUseStrictEquality();
