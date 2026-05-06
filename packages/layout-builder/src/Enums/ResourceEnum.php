<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Resources\Sections\SectionResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;

enum ResourceEnum: string
{
    case Section = SectionResource::class;

    case Widget = WidgetResource::class;
}
