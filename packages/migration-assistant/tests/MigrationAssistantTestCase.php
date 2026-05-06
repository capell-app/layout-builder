<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Tests;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\MigrationAssistant\Filament\Pages\ImportSitesPage;
use Capell\MigrationAssistant\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\MigrationAssistant\Providers\MigrationAssistantServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

abstract class MigrationAssistantTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-migration-assistant';
    }

    /** @return array<int, class-string> */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LivewireServiceProvider::class,
            AdminServiceProvider::class,
            MigrationAssistantServiceProvider::class,
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
            MigrationAssistantServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../'),
        );
        CapellCore::forcePackageInstalled(MigrationAssistantServiceProvider::$packageName);

        CapellAdmin::contributeToAdminSurface(
            AdminSurfaceContributionData::resource(ImportSessionResource::class, group: 'ImportSession'),
        );
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(ImportSitesPage::class));
    }
}
