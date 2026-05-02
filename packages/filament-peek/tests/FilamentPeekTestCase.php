<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\FilamentPeek\Providers\FilamentPeekServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;
use Pboivin\FilamentPeek\FilamentPeekServiceProvider as BaseFilamentPeekServiceProvider;

abstract class FilamentPeekTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-filament-peek';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            BaseFilamentPeekServiceProvider::class,
            FilamentPeekServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::registerPackage(
            'capell-app/workspaces',
            path: realpath(__DIR__ . '/../../workspaces'),
        );
        CapellCore::forcePackageInstalled('capell-app/workspaces');
        CapellCore::forcePackageInstalled(FilamentPeekServiceProvider::$packageName);
    }
}
