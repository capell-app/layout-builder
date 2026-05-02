<?php

declare(strict_types=1);

namespace Capell\Address\Enums;

use Capell\Address\Filament\Configurators\Addresses\DefaultAddressConfigurator;
use Capell\Address\Filament\Configurators\Countries\DefaultCountryConfigurator;
use Capell\Admin\Concerns\HasConfiguratorTypes;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;

enum ConfiguratorTypeEnum: string implements ConfiguratorTypeEnumInterface
{
    use HasConfiguratorTypes;

    case Address = 'Addresses';

    case Country = 'Countries';

    public function getConfigurators(): array
    {
        return match ($this) {
            self::Address => [
                DefaultAddressConfigurator::class,
            ],
            self::Country => [
                DefaultCountryConfigurator::class,
            ],
        };
    }
}
