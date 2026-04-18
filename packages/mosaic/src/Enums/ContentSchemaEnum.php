<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Resources\Sections\Schemas\Types\DefaultContentSchema;
use Capell\Mosaic\Filament\Resources\Sections\Schemas\Types\HeroContentSchema;
use Capell\Mosaic\Filament\Resources\Sections\Schemas\Types\TestimonialContentSchema;

enum ContentSchemaEnum: string
{
    case Default = DefaultContentSchema::class;

    case Hero = HeroContentSchema::class;

    case Testimonial = TestimonialContentSchema::class;
}
