<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Resources\Contents\Schemas\Types\DefaultContentSchema;
use Capell\Mosaic\Filament\Resources\Contents\Schemas\Types\TestimonialContentSchema;

enum ContentSchemaEnum: string
{
    case Default = DefaultContentSchema::class;

    case Testimonial = TestimonialContentSchema::class;
}
