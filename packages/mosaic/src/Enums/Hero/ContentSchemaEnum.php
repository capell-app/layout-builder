<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums\Hero;

use Capell\Mosaic\Filament\Resources\Sections\Schemas\Types\HeroContentSchema;

enum ContentSchemaEnum: string
{
    case Hero = HeroContentSchema::class;
}
