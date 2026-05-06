<?php

declare(strict_types=1);

namespace Capell\AIOrchestrator\Support;

use Capell\AIOrchestrator\Contracts\AIOrchestratorModule;
use Capell\AIOrchestrator\Data\AIOrchestratorCapabilityData;
use InvalidArgumentException;

class AIOrchestratorModuleRegistry
{
    /** @var array<string, AIOrchestratorModule> */
    private array $modules = [];

    public function register(AIOrchestratorModule $module): void
    {
        $this->modules[$module->key()] = $module;
    }

    /**
     * @return array<string, AIOrchestratorModule>
     */
    public function modules(): array
    {
        return $this->modules;
    }

    public function module(string $moduleKey): AIOrchestratorModule
    {
        return $this->modules[$moduleKey]
            ?? throw new InvalidArgumentException(sprintf('AIOrchestrator module [%s] is not registered.', $moduleKey));
    }

    public function capability(string $moduleKey, string $capabilityKey): AIOrchestratorCapabilityData
    {
        foreach ($this->module($moduleKey)->capabilities() as $capability) {
            if ($capability->key === $capabilityKey) {
                return $capability;
            }
        }

        throw new InvalidArgumentException(sprintf('AIOrchestrator capability [%s:%s] is not registered.', $moduleKey, $capabilityKey));
    }

    /**
     * @return array<int, AIOrchestratorCapabilityData>
     */
    public function capabilities(): array
    {
        return collect($this->modules)
            ->flatMap(fn (AIOrchestratorModule $module): array => $module->capabilities())
            ->values()
            ->all();
    }
}
