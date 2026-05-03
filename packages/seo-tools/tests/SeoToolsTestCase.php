<?php

declare(strict_types=1);

namespace Capell\SeoTools\Tests;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Analytics\Providers\AnalyticsServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\SeoTools\Filament\Pages\BrokenLinksPage;
use Capell\SeoTools\Filament\Pages\NotFoundUrlsPage;
use Capell\SeoTools\Filament\Pages\SEOAuditPage;
use Capell\SeoTools\Filament\Pages\SitemapPage;
use Capell\SeoTools\Filament\Pages\TranslationCoveragePage;
use Capell\SeoTools\Providers\SeoToolsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;
use Override;

class SeoToolsTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-seo-tools';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminServiceProvider::class,
            AnalyticsServiceProvider::class,
            SeoToolsServiceProvider::class,
            AdminPanelProvider::class,
            FrontendServiceProvider::class,
            LivewireServiceProvider::class,
            NavigationServiceProvider::class,
            PaginateRouteServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::registerPackage(
            AnalyticsServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../../analytics'),
        );
        CapellCore::forcePackageInstalled(AnalyticsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(SeoToolsServiceProvider::$packageName);

        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(NotFoundUrlsPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(BrokenLinksPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(SEOAuditPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(TranslationCoveragePage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(SitemapPage::class));

        // Register navigation with its path so BuildsOrderedMigrationWorkspace can
        // discover and include navigation's migrations in the ordered workspace.
        CapellCore::registerPackage(
            NavigationServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../../navigation'),
        );
        CapellCore::forcePackageInstalled(NavigationServiceProvider::$packageName);
    }
}
