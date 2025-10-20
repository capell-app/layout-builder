<?php

declare(strict_types=1);

namespace Capell\Address\Enums;

use Capell\Address\Models\Address;
use Capell\Address\Models\Country;

enum ModelEnum: string
{
    case Address = Address::class;
    case Country = Country::class;
}
