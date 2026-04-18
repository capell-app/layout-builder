<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Resources\Sections\ContentResource;
use Capell\Mosaic\Filament\Resources\Widgets\WidgetResource;

enum ResourceEnum: string
{
    case Content = ContentResource::class;

    case Widget = WidgetResource::class;
}
