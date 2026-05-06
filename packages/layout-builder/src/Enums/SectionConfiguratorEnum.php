<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Configurators\Sections\DefaultSectionConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Sections\HeroSectionConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Sections\TestimonialSectionConfigurator;

enum SectionConfiguratorEnum: string
{
    case Default = DefaultSectionConfigurator::class;

    case Hero = HeroSectionConfigurator::class;

    case Testimonial = TestimonialSectionConfigurator::class;
}
