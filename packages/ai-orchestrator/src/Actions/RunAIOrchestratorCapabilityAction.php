<?php

declare(strict_types=1);

namespace Capell\AIOrchestrator\Actions;

use Capell\AIOrchestrator\Data\AIOrchestratorRunData;
use Capell\AIOrchestrator\Support\AIOrchestratorModuleRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class RunAIOrchestratorCapabilityAction
{
    use AsObject;

    public function handle(AIOrchestratorRunData $run): mixed
    {
        $capability = resolve(AIOrchestratorModuleRegistry::class)
            ->capability($run->moduleKey, $run->capabilityKey);

        return $capability->actionClass::run($run);
    }
}
