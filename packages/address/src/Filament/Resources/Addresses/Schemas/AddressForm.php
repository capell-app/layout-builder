<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Addresses\Schemas;

use Capell\Address\Enums\SchemaTypeEnum;
use Capell\Address\Filament\Schemas\Addresses\DefaultAddressSchema;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Filament\Schemas\Schema;

class AddressForm implements FormConfigurator
{
    public static function configure(Schema $schema): Schema
    {
        $adminType = CapellAdmin::getSchema(SchemaTypeEnum::Address->value, DefaultAddressSchema::getKey());

        return $schema
            ->components(resolve($adminType)->make($schema))
            ->columns();
    }
}
