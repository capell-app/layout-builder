<?php

declare(strict_types=1);

use Capell\MigrationAssistant\Tests\MigrationAssistantTestCase;

pest()->extend(MigrationAssistantTestCase::class)->group('migration-assistant')->in(__DIR__);
