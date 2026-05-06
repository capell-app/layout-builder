<?php

declare(strict_types=1);

namespace Capell\AIOrchestrator\Contracts;

use Capell\AIOrchestrator\Data\AIOrchestratorCapabilityData;

interface AIOrchestratorModule
{
    public function key(): string;

    public function label(): string;

    /**
     * @return array<int, AIOrchestratorCapabilityData>
     */
    public function capabilities(): array;
}
