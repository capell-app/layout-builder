<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Schemas\Widget\AssetsWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\CarouselWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\DefaultWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\MediaWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\NavigationWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\PageContentWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\RelatedWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\ResultsWidgetSchema;
use Capell\Layout\Filament\Schemas\Widget\SystemWidgetSchema;

enum WidgetSchemaEnum: string
{
    case Default = DefaultWidgetSchema::class;
    case Media = MediaWidgetSchema::class;
    case Navigation = NavigationWidgetSchema::class;
    case PageContent = PageContentWidgetSchema::class;
    case Carousel = CarouselWidgetSchema::class;
    case Assets = AssetsWidgetSchema::class;
    case Results = ResultsWidgetSchema::class;
    case Related = RelatedWidgetSchema::class;
    case System = SystemWidgetSchema::class;
}
