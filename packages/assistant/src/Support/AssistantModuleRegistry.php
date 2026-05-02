<?php

declare(strict_types=1);

namespace Capell\Assistant\Support;

use Capell\Assistant\Contracts\AssistantModule;
use Capell\Assistant\Data\AssistantCapabilityData;
use InvalidArgumentException;

class AssistantModuleRegistry
{
    /** @var array<string, AssistantModule> */
    private array $modules = [];

    public function register(AssistantModule $module): void
    {
        $this->modules[$module->key()] = $module;
    }

    /**
     * @return array<string, AssistantModule>
     */
    public function modules(): array
    {
        return $this->modules;
    }

    public function module(string $moduleKey): AssistantModule
    {
        return $this->modules[$moduleKey]
            ?? throw new InvalidArgumentException(sprintf('Assistant module [%s] is not registered.', $moduleKey));
    }

    public function capability(string $moduleKey, string $capabilityKey): AssistantCapabilityData
    {
        foreach ($this->module($moduleKey)->capabilities() as $capability) {
            if ($capability->key === $capabilityKey) {
                return $capability;
            }
        }

        throw new InvalidArgumentException(sprintf('Assistant capability [%s:%s] is not registered.', $moduleKey, $capabilityKey));
    }

    /**
     * @return array<int, AssistantCapabilityData>
     */
    public function capabilities(): array
    {
        return collect($this->modules)
            ->flatMap(fn (AssistantModule $module): array => $module->capabilities())
            ->values()
            ->all();
    }
}
