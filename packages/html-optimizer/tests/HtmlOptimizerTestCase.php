<?php

declare(strict_types=1);

namespace Capell\HtmlOptimizer\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\HtmlOptimizer\Providers\HtmlOptimizerServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

abstract class HtmlOptimizerTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-html-optimizer';
    }

    /** @return array<int, class-string> */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LivewireServiceProvider::class,
            HtmlOptimizerServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::registerPackage(
            HtmlOptimizerServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../'),
        );
        CapellCore::forcePackageInstalled(HtmlOptimizerServiceProvider::$packageName);
    }
}
