<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Tests;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Frontend\Support\CapellFrontendContext;
use Capell\Frontend\Support\State\FrontendState;
use Capell\Insights\Providers\InsightsServiceProvider;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\SeoSuite\Filament\Pages\AiDiscoveryPage;
use Capell\SeoSuite\Filament\Pages\BrokenLinksPage;
use Capell\SeoSuite\Filament\Pages\NotFoundUrlsPage;
use Capell\SeoSuite\Filament\Pages\SeoAuditPage;
use Capell\SeoSuite\Filament\Pages\SitemapPage;
use Capell\SeoSuite\Filament\Pages\TranslationCoveragePage;
use Capell\SeoSuite\Providers\SeoSuiteServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Contracts\Foundation\Application;
use Livewire\LivewireServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;
use Override;

class SeoSuiteTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-seo-suite';
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
            InsightsServiceProvider::class,
            SeoSuiteServiceProvider::class,
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

        $app->scoped(FrontendState::class, fn (): FrontendState => new FrontendState);
        $app->scoped(FrontendContextReader::class, fn (Application $application): FrontendState => $application->make(FrontendState::class));
        $app->scoped(CapellFrontendContext::class, fn (Application $application): CapellFrontendContext => new CapellFrontendContext($application->make(FrontendContextReader::class)));
        $app->alias(CapellFrontendContext::class, 'capell.frontend.context');

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::registerPackage(
            FrontendServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../../frontend'),
        );
        CapellCore::registerPackage(
            InsightsServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../../insights'),
        );
        CapellCore::forcePackageInstalled(InsightsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(SeoSuiteServiceProvider::$packageName);

        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(NotFoundUrlsPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(BrokenLinksPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(SeoAuditPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(AiDiscoveryPage::class));
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
