<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Configurators\Widgets\AssetsWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Widgets\CardGridWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Widgets\CarouselWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Widgets\CTASectionWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Widgets\FeatureListWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Widgets\HeroBannerWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Widgets\HeroWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Widgets\ImageGalleryWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Widgets\NavigationWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Widgets\PageContentWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Widgets\ResultsWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Widgets\SystemWidgetConfigurator;

enum WidgetConfiguratorEnum: string
{
    case Default = DefaultWidgetConfigurator::class;
    case Assets = AssetsWidgetConfigurator::class;
    case Carousel = CarouselWidgetConfigurator::class;
    case Hero = HeroWidgetConfigurator::class;
    case Navigation = NavigationWidgetConfigurator::class;
    case PageContent = PageContentWidgetConfigurator::class;
    case Results = ResultsWidgetConfigurator::class;
    case System = SystemWidgetConfigurator::class;

    case HeroBanner = HeroBannerWidgetConfigurator::class;

    case CardGrid = CardGridWidgetConfigurator::class;

    case FeatureList = FeatureListWidgetConfigurator::class;

    case CTASection = CTASectionWidgetConfigurator::class;

    case ImageGallery = ImageGalleryWidgetConfigurator::class;
}
