<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Tests;

use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Frontend\Support\CapellFrontendContext;
use Capell\Frontend\Support\State\FrontendState;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\SiteDiscovery\Providers\SiteDiscoveryServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Contracts\Foundation\Application;
use Livewire\LivewireServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;
use Override;

class SiteDiscoveryTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-site-discovery';
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
            SiteDiscoveryServiceProvider::class,
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
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(SiteDiscoveryServiceProvider::$packageName);

        CapellCore::registerPackage(
            NavigationServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../../navigation'),
        );
        CapellCore::forcePackageInstalled(NavigationServiceProvider::$packageName);
    }
}
