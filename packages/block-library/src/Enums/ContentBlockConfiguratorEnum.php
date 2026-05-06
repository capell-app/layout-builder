<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Enums;

use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\AccordionContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\CallToActionContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\ComparisonContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\CounterContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\DefaultContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\DividerContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\FaqContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\FeaturesContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\HeroContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\LogosContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\PricingContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\StatsContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\TableContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\TabsContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\TeamContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\TestimonialContentBlockConfigurator;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\TimelineContentBlockConfigurator;

enum ContentBlockConfiguratorEnum: string
{
    case Default = DefaultContentBlockConfigurator::class;

    case Hero = HeroContentBlockConfigurator::class;

    case Testimonial = TestimonialContentBlockConfigurator::class;

    case Accordion = AccordionContentBlockConfigurator::class;

    case CallToAction = CallToActionContentBlockConfigurator::class;

    case Comparison = ComparisonContentBlockConfigurator::class;

    case Counter = CounterContentBlockConfigurator::class;

    case Divider = DividerContentBlockConfigurator::class;

    case Faq = FaqContentBlockConfigurator::class;

    case Features = FeaturesContentBlockConfigurator::class;

    case Logos = LogosContentBlockConfigurator::class;

    case Pricing = PricingContentBlockConfigurator::class;

    case Stats = StatsContentBlockConfigurator::class;

    case Table = TableContentBlockConfigurator::class;

    case Tabs = TabsContentBlockConfigurator::class;

    case Team = TeamContentBlockConfigurator::class;

    case Timeline = TimelineContentBlockConfigurator::class;
}
