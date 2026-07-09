<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetExtensions;

use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionCollisionData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;

final class WidgetExtensionRegistry
{
    /** @var array<string, WidgetExtensionDefinitionData> */
    private array $definitions = [];

    /** @var list<WidgetExtensionCollisionData> */
    private array $collisions = [];

    public function __construct(
        private readonly ?WidgetExtensionDefinitionAdapter $adapter = null,
    ) {}

    public function register(WidgetExtensionDefinitionData $definition): void
    {
        $existing = $this->definitions[$definition->key] ?? null;

        if ($existing?->equals($definition)) {
            return;
        }

        if ($existing !== null) {
            foreach ($this->collisions as $collision) {
                if ($collision->acceptedDefinition->equals($existing)
                    && $collision->conflictingDefinition->equals($definition)) {
                    return;
                }
            }

            $this->collisions[] = new WidgetExtensionCollisionData(
                key: $definition->key,
                acceptedPackageName: $existing->packageName,
                conflictingPackageName: $definition->packageName,
                acceptedDefinition: $existing,
                conflictingDefinition: $definition,
            );

            return;
        }

        $this->definitions[$definition->key] = $definition;
        $this->adapter?->adapt($definition);
    }

    public function definition(string $key): ?WidgetExtensionDefinitionData
    {
        return $this->definitions[$key] ?? null;
    }

    /** @return array<string, WidgetExtensionDefinitionData> */
    public function all(): array
    {
        return $this->definitions;
    }

    /** @return list<WidgetExtensionCollisionData> */
    public function collisions(): array
    {
        return $this->collisions;
    }
}
