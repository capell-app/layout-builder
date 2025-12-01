<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Countries\Schemas;

use Capell\Address\Enums\SchemaTypeEnum;
use Capell\Address\Filament\Resources\Countries\Schemas\Types\DefaultCountrySchema;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Filament\Schemas\Schema;

class CountryForm implements FormConfigurator
{
    public static function configure(Schema $schema): Schema
    {
        $adminType = CapellAdmin::getSchema(SchemaTypeEnum::Country->value, DefaultCountrySchema::getKey());

        return $schema
            ->components(resolve($adminType)->make($schema))
            ->columns();
    }
}
