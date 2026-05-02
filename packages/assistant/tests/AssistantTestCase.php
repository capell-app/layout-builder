<?php

declare(strict_types=1);

namespace Capell\Assistant\Tests;

use Capell\Assistant\Providers\AssistantServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

class AssistantTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-assistant';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AssistantServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);
    }
}
