<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Configurators\Elements\AssetsElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Elements\CardGridElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Elements\CarouselElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Elements\CTASectionElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Elements\DefaultElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Elements\FeatureListElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Elements\HeroBannerElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Elements\HeroElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Elements\ImageGalleryElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Elements\NavigationElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Elements\PageContentElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Elements\ResultsElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Elements\SystemElementConfigurator;

enum ElementConfiguratorEnum: string
{
    case Default = DefaultElementConfigurator::class;

    case Assets = AssetsElementConfigurator::class;

    case Carousel = CarouselElementConfigurator::class;

    case Hero = HeroElementConfigurator::class;

    case Navigation = NavigationElementConfigurator::class;

    case PageContent = PageContentElementConfigurator::class;

    case Results = ResultsElementConfigurator::class;

    case System = SystemElementConfigurator::class;

    case HeroBanner = HeroBannerElementConfigurator::class;

    case CardGrid = CardGridElementConfigurator::class;

    case FeatureList = FeatureListElementConfigurator::class;

    case CTASection = CTASectionElementConfigurator::class;

    case ImageGallery = ImageGalleryElementConfigurator::class;
}
