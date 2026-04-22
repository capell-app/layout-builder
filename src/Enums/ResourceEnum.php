<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Resources\Sections\SectionResource;
use Capell\Mosaic\Filament\Resources\Widgets\WidgetResource;

enum ResourceEnum: string
{
    case Section = SectionResource::class;

    case Widget = WidgetResource::class;
}
