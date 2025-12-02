<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Resources\Contents\ContentResource;
use Capell\Layout\Filament\Resources\Widgets\WidgetResource;

enum ResourceEnum: string
{
    case Content = ContentResource::class;

    case Widget = WidgetResource::class;
}
