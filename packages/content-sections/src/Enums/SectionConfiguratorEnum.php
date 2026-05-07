<?php

declare(strict_types=1);

namespace Capell\ContentSections\Enums;

use Capell\ContentSections\Filament\Configurators\Sections\AccordionSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\CallToActionSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\ComparisonSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\CounterSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\DefaultSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\DividerSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\FaqSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\FeaturesSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\HeroSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\LogosSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\PricingSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\StatsSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\TableSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\TabsSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\TeamSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\TestimonialSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\TimelineSectionConfigurator;

enum SectionConfiguratorEnum: string
{
    case Default = DefaultSectionConfigurator::class;

    case Hero = HeroSectionConfigurator::class;

    case Testimonial = TestimonialSectionConfigurator::class;

    case Accordion = AccordionSectionConfigurator::class;

    case CallToAction = CallToActionSectionConfigurator::class;

    case Comparison = ComparisonSectionConfigurator::class;

    case Counter = CounterSectionConfigurator::class;

    case Divider = DividerSectionConfigurator::class;

    case Faq = FaqSectionConfigurator::class;

    case Features = FeaturesSectionConfigurator::class;

    case Logos = LogosSectionConfigurator::class;

    case Pricing = PricingSectionConfigurator::class;

    case Stats = StatsSectionConfigurator::class;

    case Table = TableSectionConfigurator::class;

    case Tabs = TabsSectionConfigurator::class;

    case Team = TeamSectionConfigurator::class;

    case Timeline = TimelineSectionConfigurator::class;
}
