<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Resources\Layouts\Schemas\Types\Containers\DefaultLayoutContainerSchema;

enum LayoutContainerSchemaEnum: string
{
    case Default = DefaultLayoutContainerSchema::class;
}
