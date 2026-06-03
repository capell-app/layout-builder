<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Configurators\Widgets\AssetsWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\CardGridWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\CarouselWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\CTASectionWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\FeatureListWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\HeroBannerWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\HeroWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ImageGalleryWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\KitchenSinkReferenceWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\NavigationWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\PageContentWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ResultsWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\SystemWidgetConfigurator;

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

    case KitchenSinkReference = KitchenSinkReferenceWidgetConfigurator::class;
}
