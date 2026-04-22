<?php

declare(strict_types=1);

namespace Capell\Address\Enums;

use Capell\Address\Filament\Schemas\Addresses\DefaultAddressSchema;

enum AddressSchemaEnum: string
{
    case Default = DefaultAddressSchema::class;
}
