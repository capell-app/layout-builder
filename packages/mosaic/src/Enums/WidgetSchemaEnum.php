<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\AssetsWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\CarouselWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\DefaultWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\NavigationWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\PageContentWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\ResultsWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\SystemWidgetSchema;

enum WidgetSchemaEnum: string
{
    case Default = DefaultWidgetSchema::class;
    case Assets = AssetsWidgetSchema::class;
    case Carousel = CarouselWidgetSchema::class;
    case Navigation = NavigationWidgetSchema::class;
    case PageContent = PageContentWidgetSchema::class;
    case Results = ResultsWidgetSchema::class;
    case System = SystemWidgetSchema::class;
}
