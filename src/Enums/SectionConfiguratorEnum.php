<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Configurators\Sections\DefaultSectionConfigurator;
use Capell\Mosaic\Filament\Configurators\Sections\HeroSectionConfigurator;
use Capell\Mosaic\Filament\Configurators\Sections\TestimonialSectionConfigurator;

enum SectionConfiguratorEnum: string
{
    case Default = DefaultSectionConfigurator::class;

    case Hero = HeroSectionConfigurator::class;

    case Testimonial = TestimonialSectionConfigurator::class;
}
