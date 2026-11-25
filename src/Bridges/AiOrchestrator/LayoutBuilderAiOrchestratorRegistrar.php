<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Bridges\AiOrchestrator;

use Capell\AIOrchestrator\Support\AIOrchestratorModuleRegistry;
use Capell\Core\Facades\CapellCore;
use Illuminate\Contracts\Foundation\Application;

/** Registers the Layout Builder planning module with AI Orchestrator; nothing when it is not available. */
final class LayoutBuilderAiOrchestratorRegistrar
{
    public static function register(Application $app): void
    {
        if (! CapellCore::isPackageAvailable('capell-app/ai-orchestrator')) {
            return;
        }

        $register = static function (AIOrchestratorModuleRegistry $registry): void {
            if (! isset($registry->modules()['layout-builder'])) {
                $registry->register(new LayoutBuilderAIOrchestratorModule);
            }
        };

        $app->afterResolving(AIOrchestratorModuleRegistry::class, $register);

        if ($app->resolved(AIOrchestratorModuleRegistry::class)) {
            $register($app->make(AIOrchestratorModuleRegistry::class));
        }
    }
}
