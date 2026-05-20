<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Configurators\Blocks\AssetsBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\CardGridBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\CarouselBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\CTASectionBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\DefaultBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\FeatureListBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\HeroBannerBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\HeroBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ImageGalleryBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\NavigationBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\PageContentBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ResultsBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\SystemBlockConfigurator;

enum BlockConfiguratorEnum: string
{
    case Default = DefaultBlockConfigurator::class;

    case Assets = AssetsBlockConfigurator::class;

    case Carousel = CarouselBlockConfigurator::class;

    case Hero = HeroBlockConfigurator::class;

    case Navigation = NavigationBlockConfigurator::class;

    case PageContent = PageContentBlockConfigurator::class;

    case Results = ResultsBlockConfigurator::class;

    case System = SystemBlockConfigurator::class;

    case HeroBanner = HeroBannerBlockConfigurator::class;

    case CardGrid = CardGridBlockConfigurator::class;

    case FeatureList = FeatureListBlockConfigurator::class;

    case CTASection = CTASectionBlockConfigurator::class;

    case ImageGallery = ImageGalleryBlockConfigurator::class;
}
