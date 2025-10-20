<?php

declare(strict_types=1);

namespace Capell\Hero\Enums;

use Capell\Hero\Filament\Resources\Widgets\Schemas\Types\HeroWidgetSchema;

enum WidgetSchemaEnum: string
{
    case Hero = HeroWidgetSchema::class;
}
