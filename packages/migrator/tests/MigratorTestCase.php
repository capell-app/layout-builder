<?php

declare(strict_types=1);

namespace Capell\Migrator\Tests;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Migrator\Filament\Pages\ImportSitesPage;
use Capell\Migrator\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\Migrator\Providers\MigratorServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

abstract class MigratorTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-migrator';
    }

    /** @return array<int, class-string> */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LivewireServiceProvider::class,
            AdminServiceProvider::class,
            MigratorServiceProvider::class,
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
            MigratorServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../'),
        );
        CapellCore::forcePackageInstalled(MigratorServiceProvider::$packageName);

        CapellAdmin::contributeToAdminSurface(
            AdminSurfaceContributionData::resource(ImportSessionResource::class, group: 'ImportSession'),
        );
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(ImportSitesPage::class));
    }
}
