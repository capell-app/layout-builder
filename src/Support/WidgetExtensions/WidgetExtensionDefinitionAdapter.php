<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetExtensions;

use Capell\Admin\Support\Widgets\WidgetDiscovery;
use Capell\LayoutBuilder\Data\LayoutWidgets\LayoutWidgetDefinitionData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;
use Illuminate\Contracts\Container\Container;

final class WidgetExtensionDefinitionAdapter
{
    /** @var array<string, true> */
    private array $adaptedLayoutDefinitions = [];

    /** @var array<string, true> */
    private array $adaptedFilamentWidgets = [];

    public function __construct(
        private readonly Container $container,
    ) {}

    public function adapt(WidgetExtensionDefinitionData $definition): void
    {
        $registerLayoutDefinition = function (LayoutWidgetRegistry $registry) use ($definition): void {
            $registrationKey = spl_object_id($registry) . ':' . $definition->key;

            if (isset($this->adaptedLayoutDefinitions[$registrationKey])) {
                return;
            }

            $registry->registerDefinition(LayoutWidgetDefinitionData::frontendBlade(
                key: $definition->key,
                component: $definition->fallbackView,
                resourceGroups: $definition->resourceGroups,
                defaultPresentationSettings: $definition->defaultPresentationSettings,
                defaultInteractionTriggers: $definition->defaultInteractions,
            ));
            $this->adaptedLayoutDefinitions[$registrationKey] = true;
        };

        $registerFilamentWidget = function (WidgetDiscovery $discovery) use ($definition): void {
            $registrationKey = spl_object_id($discovery) . ':' . $definition->key;

            if (isset($this->adaptedFilamentWidgets[$registrationKey])) {
                return;
            }

            $discovery->register($definition->filamentWidget);
            $this->adaptedFilamentWidgets[$registrationKey] = true;
        };

        $this->container->afterResolving(LayoutWidgetRegistry::class, $registerLayoutDefinition);
        $this->container->afterResolving(WidgetDiscovery::class, $registerFilamentWidget);

        if ($this->container->resolved(LayoutWidgetRegistry::class)) {
            $registerLayoutDefinition($this->container->make(LayoutWidgetRegistry::class));
        }

        if ($this->container->resolved(WidgetDiscovery::class)) {
            $registerFilamentWidget($this->container->make(WidgetDiscovery::class));
        }
    }
}
