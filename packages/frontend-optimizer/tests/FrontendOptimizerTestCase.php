<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\FrontendOptimizer\Providers\FrontendOptimizerServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

abstract class FrontendOptimizerTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-frontend-optimizer';
    }

    /** @return array<int, class-string> */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LivewireServiceProvider::class,
            FrontendOptimizerServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::registerPackage(
            FrontendOptimizerServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../'),
        );
        CapellCore::forcePackageInstalled(FrontendOptimizerServiceProvider::$packageName);
    }
}
