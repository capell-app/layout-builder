<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Feature\Providers;

use Capell\AIOrchestrator\Providers\AIOrchestratorServiceProvider;
use Capell\AIOrchestrator\Support\AIOrchestratorModuleRegistry;
use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\AIOrchestrator\LayoutBuilderAIOrchestratorModule;
use Capell\LayoutBuilder\Tests\LayoutBuilderTestCase;
use Illuminate\Foundation\Application;
use Override;

final class LayoutBuilderAIOrchestratorModuleRegistrationWithAIOrchestratorTest extends LayoutBuilderTestCase
{
    public function test_it_registers_layout_builder_ai_orchestrator_module_when_ai_orchestrator_is_installed(): void
    {
        $registry = $this->app->make(AIOrchestratorModuleRegistry::class);

        $this->assertInstanceOf(LayoutBuilderAIOrchestratorModule::class, $registry->module('layout-builder'));
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AIOrchestratorServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AIOrchestratorServiceProvider::$packageName);
    }
}
