<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;

enum ResourceEnum: string
{
    case Widget = WidgetResource::class;
}
