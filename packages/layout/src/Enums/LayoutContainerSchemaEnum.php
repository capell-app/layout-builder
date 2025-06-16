<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Schemas;

enum LayoutContainerSchemaEnum: string
{
    case Default = Schemas\LayoutContainer\DefaultLayoutContainerSchema::class;
}
