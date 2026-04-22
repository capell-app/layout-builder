<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Schemas\Sections\DefaultSectionSchema;
use Capell\Mosaic\Filament\Schemas\Sections\HeroSectionSchema;
use Capell\Mosaic\Filament\Schemas\Sections\TestimonialSectionSchema;

enum SectionSchemaEnum: string
{
    case Default = DefaultSectionSchema::class;

    case Hero = HeroSectionSchema::class;

    case Testimonial = TestimonialSectionSchema::class;
}
