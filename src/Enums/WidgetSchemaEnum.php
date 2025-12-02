<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\AssetsWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\CarouselWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\DefaultWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\NavigationWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\PageContentWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\ResultsWidgetSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\SystemWidgetSchema;

enum WidgetSchemaEnum: string
{
    case Default = DefaultWidgetSchema::class;

    case Navigation = NavigationWidgetSchema::class;

    case PageContent = PageContentWidgetSchema::class;

    case Carousel = CarouselWidgetSchema::class;

    case Assets = AssetsWidgetSchema::class;

    case Results = ResultsWidgetSchema::class;

    case System = SystemWidgetSchema::class;
}
