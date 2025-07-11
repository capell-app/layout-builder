<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas;

use Capell\Admin\Filament\Schemas\AbstractSchema;
use Capell\Layout\Enums\SchemaEnum;

abstract class AbstractLayoutContainerSchema extends AbstractSchema
{
    protected static string $schemaType = SchemaEnum::LayoutContainer->value;
}
