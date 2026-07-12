<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\LayoutWidgets;

use Capell\LayoutBuilder\Data\LayoutWidgets\LayoutWidgetDefinitionData;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;

class LayoutWidgetRegistry
{
    /** @var array<string, array<string, string>> */
    private array $widgets = [];

    /** @var array<string, array<string, LayoutWidgetDefinitionData>> */
    private array $definitions = [];

    /** @var array<string, true> */
    private array $authoritativeDefinitions = [];

    public function register(string $name, LayoutWidgetTarget $target, string $component): void
    {
        $this->registerDefinition(new LayoutWidgetDefinitionData(
            key: $name,
            target: $target,
            component: $component,
        ));
    }

    public function registerDefinition(LayoutWidgetDefinitionData $definition): void
    {
        if (isset($this->authoritativeDefinitions[$this->registrationKey($definition)])) {
            return;
        }

        $this->store($definition);
    }

    public function registerAuthoritativeDefinition(LayoutWidgetDefinitionData $definition): void
    {
        $registrationKey = $this->registrationKey($definition);

        if (isset($this->authoritativeDefinitions[$registrationKey])) {
            return;
        }

        $this->store($definition);
        $this->authoritativeDefinitions[$registrationKey] = true;
    }

    public function get(string $name, LayoutWidgetTarget $target): ?string
    {
        return $this->widgets[$target->value][$name] ?? null;
    }

    public function definition(string $name, LayoutWidgetTarget $target): ?LayoutWidgetDefinitionData
    {
        return $this->definitions[$target->value][$name] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function allForTarget(LayoutWidgetTarget $target): array
    {
        return $this->widgets[$target->value] ?? [];
    }

    /**
     * @return array<string, LayoutWidgetDefinitionData>
     */
    public function allDefinitionsForTarget(LayoutWidgetTarget $target): array
    {
        return $this->definitions[$target->value] ?? [];
    }

    private function store(LayoutWidgetDefinitionData $definition): void
    {
        $this->widgets[$definition->target->value][$definition->key] = $definition->component;
        $this->definitions[$definition->target->value][$definition->key] = $definition;
    }

    private function registrationKey(LayoutWidgetDefinitionData $definition): string
    {
        return $definition->target->value . ':' . $definition->key;
    }
}
