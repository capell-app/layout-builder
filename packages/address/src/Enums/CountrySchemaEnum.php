<?php

declare(strict_types=1);

namespace Capell\Address\Enums;

use Capell\Address\Filament\Schemas\Countries\DefaultCountrySchema;

enum CountrySchemaEnum: string
{
    case Default = DefaultCountrySchema::class;
}
