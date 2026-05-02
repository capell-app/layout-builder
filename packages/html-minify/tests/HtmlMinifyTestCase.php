<?php

declare(strict_types=1);

namespace Capell\HtmlMinify\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\HtmlMinify\Providers\HtmlMinifyServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

abstract class HtmlMinifyTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-html-minify';
    }

    /** @return array<int, class-string> */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LivewireServiceProvider::class,
            HtmlMinifyServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::registerPackage(
            HtmlMinifyServiceProvider::$packageName,
            path: realpath(__DIR__ . '/../'),
        );
        CapellCore::forcePackageInstalled(HtmlMinifyServiceProvider::$packageName);
    }
}
