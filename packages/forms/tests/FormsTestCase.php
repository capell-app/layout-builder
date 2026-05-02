<?php

declare(strict_types=1);

namespace Capell\Forms\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\Forms\Providers\FormsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

class FormsTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-forms';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FormsServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(FormsServiceProvider::$packageName);
    }
}
