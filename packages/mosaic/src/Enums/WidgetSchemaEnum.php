<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Schemas\Widgets\AssetsWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\CardGridWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\CarouselWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\CTASectionWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\DefaultWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\FeatureListWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\HeroBannerWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\HeroWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\ImageGalleryWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\NavigationWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\PageContentWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\ResultsWidgetSchema;
use Capell\Mosaic\Filament\Schemas\Widgets\SystemWidgetSchema;

enum WidgetSchemaEnum: string
{
    case Default = DefaultWidgetSchema::class;
    case Assets = AssetsWidgetSchema::class;
    case Carousel = CarouselWidgetSchema::class;
    case Hero = HeroWidgetSchema::class;
    case Navigation = NavigationWidgetSchema::class;
    case PageContent = PageContentWidgetSchema::class;
    case Results = ResultsWidgetSchema::class;
    case System = SystemWidgetSchema::class;

    case HeroBanner = HeroBannerWidgetSchema::class;

    case CardGrid = CardGridWidgetSchema::class;

    case FeatureList = FeatureListWidgetSchema::class;

    case CTASection = CTASectionWidgetSchema::class;

    case ImageGallery = ImageGalleryWidgetSchema::class;
}
