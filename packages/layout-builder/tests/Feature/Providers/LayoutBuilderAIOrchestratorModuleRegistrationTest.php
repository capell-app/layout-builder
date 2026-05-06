<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Feature\Providers;

use Capell\AIOrchestrator\Support\AIOrchestratorModuleRegistry;
use Capell\LayoutBuilder\Tests\LayoutBuilderTestCase;

final class LayoutBuilderAIOrchestratorModuleRegistrationTest extends LayoutBuilderTestCase
{
    public function test_it_skips_layout_builder_ai_orchestrator_module_when_ai_orchestrator_is_not_installed(): void
    {
        $registry = $this->app->make(AIOrchestratorModuleRegistry::class);

        $this->assertSame([], $registry->modules());
    }
}
