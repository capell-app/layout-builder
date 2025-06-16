<?php

declare(strict_types=1);

arch()
    ->expect('Capell\Blog')
    ->toOnlyBeUsedIn('Capell\Blog')
    ->ignoring([
        Capell\Admin\Commands\InstallCommand::class,
        Capell\Admin\Commands\DemoCommand::class,
        Capell\Admin\Services\Creator\DemoCreator::class,
        Capell\Core\Database\Factories\TypeFactory::class,
    ]);
