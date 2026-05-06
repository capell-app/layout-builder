<?php

declare(strict_types=1);

namespace Capell\ContentSections\Enums;

use Capell\ContentSections\Filament\Configurators\Sections\DefaultSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\HeroSectionConfigurator;
use Capell\ContentSections\Filament\Configurators\Sections\TestimonialSectionConfigurator;

enum SectionConfiguratorEnum: string
{
    case Default = DefaultSectionConfigurator::class;

    case Hero = HeroSectionConfigurator::class;

    case Testimonial = TestimonialSectionConfigurator::class;
}
