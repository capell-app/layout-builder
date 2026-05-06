<?php

declare(strict_types=1);

namespace Capell\AIOrchestrator\Contracts;

use Capell\AIOrchestrator\Data\AIOrchestratorRunData;

interface AIOrchestratorProviderConnector
{
    public function key(): string;

    public function label(): string;

    /**
     * @return array<string, mixed>
     */
    public function complete(AIOrchestratorRunData $run): array;
}
