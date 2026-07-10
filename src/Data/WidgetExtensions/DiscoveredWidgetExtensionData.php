<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\WidgetExtensions;

use Spatie\LaravelData\Data;

final class DiscoveredWidgetExtensionData extends Data
{
    /** @param array<string, mixed> $widget */
    public function __construct(
        public string $instanceId,
        public WidgetExtensionDefinitionData $definition,
        public array $widget,
    ) {}
}
