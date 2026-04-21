<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\AssetsWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\CardGridWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\CarouselWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\CTASectionWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\DefaultWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\FeatureListWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\HeroBannerWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\HeroWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\ImageGalleryWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\NavigationWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\PageContentWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\ResultsWidgetSchema;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\SystemWidgetSchema;

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
