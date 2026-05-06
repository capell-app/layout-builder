<?php

declare(strict_types=1);

namespace Capell\AIOrchestrator\Tests;

use Capell\AIOrchestrator\Providers\AIOrchestratorServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

class AIOrchestratorTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-ai-orchestrator';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AIOrchestratorServiceProvider::class,
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
