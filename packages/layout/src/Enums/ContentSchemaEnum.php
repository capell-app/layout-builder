<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Schemas\Content\DefaultContentSchema;
use Capell\Layout\Filament\Schemas\Content\TestimonialContentSchema;

enum ContentSchemaEnum: string
{
    case Default = DefaultContentSchema::class;
    case Testimonial = TestimonialContentSchema::class;
}
