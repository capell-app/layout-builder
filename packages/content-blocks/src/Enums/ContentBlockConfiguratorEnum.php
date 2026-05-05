<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Enums;

use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\AccordionContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\CallToActionContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\ComparisonContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\CounterContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\DefaultContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\DividerContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\FaqContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\FeaturesContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\HeroContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\LogosContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\PricingContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\StatsContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\TableContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\TabsContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\TeamContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\TestimonialContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\TimelineContentBlockConfigurator;

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
