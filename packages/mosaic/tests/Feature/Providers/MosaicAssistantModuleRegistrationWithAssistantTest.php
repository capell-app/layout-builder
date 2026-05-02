<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests\Feature\Providers;

use Capell\Assistant\Providers\AssistantServiceProvider;
use Capell\Assistant\Support\AssistantModuleRegistry;
use Capell\Core\Facades\CapellCore;
use Capell\Mosaic\Assistant\MosaicAssistantModule;
use Capell\Mosaic\Tests\MosaicTestCase;
use Illuminate\Foundation\Application;
use Override;

final class MosaicAssistantModuleRegistrationWithAssistantTest extends MosaicTestCase
{
    public function test_it_registers_mosaic_assistant_module_when_assistant_is_installed(): void
    {
        $registry = $this->app->make(AssistantModuleRegistry::class);

        $this->assertInstanceOf(MosaicAssistantModule::class, $registry->module('mosaic'));
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
            AssistantServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AssistantServiceProvider::$packageName);
    }
}
