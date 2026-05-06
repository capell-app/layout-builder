<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Enums;

use Capell\Admin\Concerns\HasConfiguratorTypes;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;

enum ConfiguratorTypeEnum: string implements ConfiguratorTypeEnumInterface
{
    use HasConfiguratorTypes;

    case ContentBlock = 'BlockLibrary';

    public function getConfigurators(): array
    {
        return match ($this) {
            self::ContentBlock => ContentBlockConfiguratorEnum::cases(),
        };
    }
}
