<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Schemas;

enum LayoutWidgetSchemaEnum: string
{
    case Default = Schemas\LayoutWidget\DefaultLayoutWidgetSchema::class;
    case Page = Schemas\LayoutWidget\PageLayoutWidgetSchema::class;
}
