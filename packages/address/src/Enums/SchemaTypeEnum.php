<?php

declare(strict_types=1);

namespace Capell\Address\Enums;

use Capell\Address\Filament\Resources\Addresses\Schemas\Types\DefaultAddressSchema;
use Capell\Address\Filament\Resources\Countries\Schemas\Types\DefaultCountrySchema;
use Capell\Admin\Concerns\HasSchemaTypes;
use Capell\Admin\Contracts\SchemaTypeEnumInterface;

enum SchemaTypeEnum: string implements SchemaTypeEnumInterface
{
    use HasSchemaTypes;

    case Address = 'Addresses';

    case Country = 'Countries';

    public function getSchemas(): array
    {
        return match ($this) {
            self::Address => [
                DefaultAddressSchema::class,
            ],
            self::Country => [
                DefaultCountrySchema::class,
            ],
        };
    }
}
