<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Schemas\Layouts\DefaultLayoutContainerSchema;

enum LayoutContainerSchemaEnum: string
{
    case Default = DefaultLayoutContainerSchema::class;
}
