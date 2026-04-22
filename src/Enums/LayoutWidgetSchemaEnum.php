<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Schemas\Layouts\Widgets\DefaultLayoutWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Layouts\Widgets\PageLayoutWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Layouts\Widgets\ResultsLayoutWidgetSchema;

enum LayoutWidgetSchemaEnum: string
{
    case Default = DefaultLayoutWidgetSchema::class;

    case Page = PageLayoutWidgetSchema::class;

    case Results = ResultsLayoutWidgetSchema::class;
}
