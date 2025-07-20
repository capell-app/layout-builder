<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Schemas\LayoutWidget\DefaultLayoutWidgetSchema;
use Capell\Layout\Filament\Schemas\LayoutWidget\PageLayoutWidgetSchema;

enum LayoutWidgetSchemaEnum: string
{
    case Default = DefaultLayoutWidgetSchema::class;
    case Page = PageLayoutWidgetSchema::class;
}
