<?php

declare(strict_types=1);

namespace Capell\Address\Enums;

use Capell\Address\Filament\Configurators\Countries\DefaultCountryConfigurator;

enum CountrySchemaEnum: string
{
    case Default = DefaultCountryConfigurator::class;
}
