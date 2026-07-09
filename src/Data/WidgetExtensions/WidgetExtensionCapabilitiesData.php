<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\WidgetExtensions;

use Spatie\LaravelData\Data;

final class WidgetExtensionCapabilitiesData extends Data
{
    public function __construct(
        public bool $supportsInteractions = false,
        public bool $requiresInstanceIdentity = false,
    ) {}
}
