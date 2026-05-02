<?php

declare(strict_types=1);

namespace Capell\Address\Enums;

use Capell\Address\Filament\Configurators\Addresses\DefaultAddressConfigurator;

enum AddressSchemaEnum: string
{
    case Default = DefaultAddressConfigurator::class;
}
