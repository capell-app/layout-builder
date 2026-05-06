<?php

declare(strict_types=1);

namespace Capell\WordPressImporter\Tests;

use Capell\MigrationAssistant\Tests\MigrationAssistantTestCase;
use Capell\WordPressImporter\Providers\WordPressImporterServiceProvider;
use Override;

abstract class WordPressImporterTestCase extends MigrationAssistantTestCase
{
    /** @return array<int, class-string> */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            WordPressImporterServiceProvider::class,
        ];
    }
}
