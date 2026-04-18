<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums\Hero;

use Capell\Hero\Filament\Resources\Contents\Schemas\Types\HeroContentSchema;

enum ContentSchemaEnum: string
{
    case Hero = HeroContentSchema::class;
}
