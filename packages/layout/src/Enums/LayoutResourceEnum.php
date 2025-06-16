<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Resources;

enum LayoutResourceEnum: string
{
    case Content = Resources\ContentResource::class;
    case Widget = Resources\WidgetResource::class;
}
