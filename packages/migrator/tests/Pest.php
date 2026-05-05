<?php

declare(strict_types=1);

use Capell\Migrator\Tests\MigratorTestCase;

pest()->extend(MigratorTestCase::class)->group('migrator')->in(__DIR__);
