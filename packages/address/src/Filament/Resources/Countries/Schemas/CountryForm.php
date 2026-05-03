<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Countries\Schemas;

use Capell\Address\Enums\ConfiguratorTypeEnum;
use Capell\Address\Filament\Configurators\Countries\DefaultCountryConfigurator;
use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\AdminSurfaceLookup;
use Filament\Schemas\Schema;

class CountryForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        $adminType = AdminSurfaceLookup::configurator(ConfiguratorTypeEnum::Country, DefaultCountryConfigurator::getKey());

        return $adminType::configure($configurator, $context)->columns();
    }
}
