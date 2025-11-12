<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Containers\DefaultLayoutContainerSchema;

enum LayoutContainerSchemaEnum: string
{
    case Default = DefaultLayoutContainerSchema::class;
}
