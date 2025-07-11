<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Schemas\WidgetAsset\DefaultWidgetAssetSchema;

enum WidgetAssetSchemaEnum: string
{
    case Default = DefaultWidgetAssetSchema::class;
}
