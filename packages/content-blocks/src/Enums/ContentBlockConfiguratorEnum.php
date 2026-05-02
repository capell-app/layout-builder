<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Enums;

use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\DefaultContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\HeroContentBlockConfigurator;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\TestimonialContentBlockConfigurator;

enum ContentBlockConfiguratorEnum: string
{
    case Default = DefaultContentBlockConfigurator::class;

    case Hero = HeroContentBlockConfigurator::class;

    case Testimonial = TestimonialContentBlockConfigurator::class;
}
