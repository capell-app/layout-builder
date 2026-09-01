<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetExtensions;

use Capell\Core\Contracts\Extensions\RecordsExtensionContributionReceipt;
use Capell\Core\Enums\ExtensionContributionType;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Illuminate\Contracts\Container\Container;

final class WidgetExtensionRegistrar
{
    public function __construct(
        private readonly Container $container,
        private readonly ?RecordsExtensionContributionReceipt $receipts = null,
    ) {}

    public function register(WidgetExtensionDefinitionData $definition): void
    {
        $registerDefinition = function (WidgetExtensionRegistry $registry) use ($definition): void {
            if (! $registry->register($definition)) {
                return;
            }

            $this->receipts?->recordContribution(
                ExtensionContributionType::ContentWidget,
                $definition->key,
                $definition->filamentWidget,
                self::class,
                'frontend',
            );
        };

        $this->container->afterResolving(WidgetExtensionRegistry::class, $registerDefinition);

        if ($this->container->resolved(WidgetExtensionRegistry::class)) {
            $registerDefinition($this->container->make(WidgetExtensionRegistry::class));
        }
    }
}
