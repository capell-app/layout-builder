<?php

declare(strict_types=1);

namespace Capell\Backup\Tests;

use Capell\Backup\Providers\BackupServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

abstract class BackupTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-backup';
    }

    /** @return array<int, class-string> */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LivewireServiceProvider::class,
            BackupServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::registerPackage(
            BackupServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../'),
        );
        CapellCore::forcePackageInstalled(BackupServiceProvider::$packageName);
    }
}
