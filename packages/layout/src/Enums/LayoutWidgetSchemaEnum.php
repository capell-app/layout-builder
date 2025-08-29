<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Widgets\DefaultLayoutWidgetSchema;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Widgets\PageLayoutWidgetSchema;

enum LayoutWidgetSchemaEnum: string
{
    case Default = DefaultLayoutWidgetSchema::class;
    case Page = PageLayoutWidgetSchema::class;
}
