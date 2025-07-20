<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Schemas\LayoutContainer\DefaultLayoutContainerSchema;

enum LayoutContainerSchemaEnum: string
{
    case Default = DefaultLayoutContainerSchema::class;
}
