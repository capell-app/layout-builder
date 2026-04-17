<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Resources\Collections\Schemas\Types\DefaultContentSchema;
use Capell\Layout\Filament\Resources\Collections\Schemas\Types\TestimonialContentSchema;

enum ContentSchemaEnum: string
{
    case Default = DefaultContentSchema::class;

    case Testimonial = TestimonialContentSchema::class;
}
