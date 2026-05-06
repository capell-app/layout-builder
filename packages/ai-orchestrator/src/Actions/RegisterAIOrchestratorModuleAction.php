<?php

declare(strict_types=1);

namespace Capell\AIOrchestrator\Actions;

use Capell\AIOrchestrator\Contracts\AIOrchestratorModule;
use Capell\AIOrchestrator\Support\AIOrchestratorModuleRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class RegisterAIOrchestratorModuleAction
{
    use AsObject;

    public function handle(AIOrchestratorModule $module): void
    {
        resolve(AIOrchestratorModuleRegistry::class)->register($module);
    }
}
