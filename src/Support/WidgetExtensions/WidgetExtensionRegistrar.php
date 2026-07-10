<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetExtensions;

use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Illuminate\Contracts\Container\Container;

final class WidgetExtensionRegistrar
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function register(WidgetExtensionDefinitionData $definition): void
    {
        $registerDefinition = static function (WidgetExtensionRegistry $registry) use ($definition): void {
            $registry->register($definition);
        };

        $this->container->afterResolving(WidgetExtensionRegistry::class, $registerDefinition);

        if ($this->container->resolved(WidgetExtensionRegistry::class)) {
            $registerDefinition($this->container->make(WidgetExtensionRegistry::class));
        }
    }
}
