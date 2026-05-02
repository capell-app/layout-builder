<?php

declare(strict_types=1);

namespace Capell\Address\Enums;

use Capell\Address\Filament\Resources\Addresses\AddressResource;
use Capell\Address\Filament\Resources\Countries\CountryResource;

enum ResourceEnum: string
{
    case Address = AddressResource::class;

    case Country = CountryResource::class;
}
