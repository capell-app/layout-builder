<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Schemas;

enum WidgetSchemaEnum: string
{
    case Default = Schemas\Widget\DefaultWidgetSchema::class;
    case Media = Schemas\Widget\MediaWidgetSchema::class;
    case Navigation = Schemas\Widget\NavigationWidgetSchema::class;
    case PageContent = Schemas\Widget\PageContentWidgetSchema::class;
    case Assets = Schemas\Widget\AssetsWidgetSchema::class;
    case Results = Schemas\Widget\ResultsWidgetSchema::class;
    case System = Schemas\Widget\SystemWidgetSchema::class;
}
