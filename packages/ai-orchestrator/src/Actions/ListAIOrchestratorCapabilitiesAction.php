<?php

declare(strict_types=1);

namespace Capell\AIOrchestrator\Actions;

use Capell\AIOrchestrator\Data\AIOrchestratorCapabilityData;
use Capell\AIOrchestrator\Support\AIOrchestratorModuleRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class ListAIOrchestratorCapabilitiesAction
{
    use AsObject;

    /**
     * @return array<int, AIOrchestratorCapabilityData>
     */
    public function handle(): array
    {
        return resolve(AIOrchestratorModuleRegistry::class)->capabilities();
    }
}
