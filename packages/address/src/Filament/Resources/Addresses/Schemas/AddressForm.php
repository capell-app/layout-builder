<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Addresses\Schemas;

use Capell\Address\Enums\ConfiguratorTypeEnum;
use Capell\Address\Filament\Configurators\Addresses\DefaultAddressConfigurator;
use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Filament\Schemas\Schema;

class AddressForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        $adminType = CapellAdmin::getConfigurator(ConfiguratorTypeEnum::Address->value, DefaultAddressConfigurator::getKey());

        return $adminType::configure($configurator, $context)->columns();
    }
}
