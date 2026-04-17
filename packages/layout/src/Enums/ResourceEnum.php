<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Resources\Collections\CollectionResource;
use Capell\Layout\Filament\Resources\Widgets\WidgetResource;

enum ResourceEnum: string
{
    case Content = CollectionResource::class;

    case Widget = WidgetResource::class;
}
