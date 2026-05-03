<?php

declare(strict_types=1);

namespace Capell\Backup\Tests;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Backup\Filament\Pages\ImportSitesPage;
use Capell\Backup\Filament\Resources\ImportSessions\ImportSessionResource;
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
            AdminServiceProvider::class,
            BackupServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::registerPackage(
            AdminServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../../../../capell-4/packages/admin'),
        );
        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);

        CapellCore::registerPackage(
            BackupServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../'),
        );
        CapellCore::forcePackageInstalled(BackupServiceProvider::$packageName);

        if (! CapellAdmin::hasResource('ImportSession')) {
            CapellAdmin::registerResource('ImportSession', ImportSessionResource::class);
        }

        CapellAdmin::registerPage(ImportSitesPage::class);
    }
}
