<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Tests\Support;

use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Frontend\Support\CapellFrontendContext;
use Capell\Frontend\Support\State\FrontendState;
use Capell\SeoSuite\Providers\SeoSuiteServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Contracts\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

class AiDiscoveryIntegrationTestCase extends AbstractTestCase
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
            SeoSuiteServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        $app->scoped(FrontendState::class, fn (): FrontendState => new FrontendState);
        $app->scoped(FrontendContextReader::class, fn (Application $application): FrontendState => $application->make(FrontendState::class));
        $app->scoped(CapellFrontendContext::class, fn (Application $application): CapellFrontendContext => new CapellFrontendContext($application->make(FrontendContextReader::class)));
        $app->alias(CapellFrontendContext::class, 'capell.frontend.context');

        CapellCore::registerPackage(
            FrontendServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../../../../../capell-4/packages/frontend'),
        );
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::registerPackage(
            SeoSuiteServiceProvider::$packageName,
            path: dirname(__DIR__, 2),
        );
        CapellCore::forcePackageInstalled(SeoSuiteServiceProvider::$packageName);
    }
}
