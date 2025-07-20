<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Resources\ContentResource;
use Capell\Layout\Filament\Resources\WidgetResource;

enum LayoutResourceEnum: string
{
    case Content = ContentResource::class;
    case Widget = WidgetResource::class;
}
