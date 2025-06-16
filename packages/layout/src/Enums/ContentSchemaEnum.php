<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Schemas;

enum ContentSchemaEnum: string
{
    case Default = Schemas\Content\DefaultContentSchema::class;
}
