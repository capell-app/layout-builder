<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\WidgetExtensions;

use Spatie\LaravelData\Data;

final class WidgetExtensionCollisionData extends Data
{
    public function __construct(
        public string $key,
        public string $acceptedPackageName,
        public string $conflictingPackageName,
        public WidgetExtensionDefinitionData $acceptedDefinition,
        public WidgetExtensionDefinitionData $conflictingDefinition,
    ) {}
}
