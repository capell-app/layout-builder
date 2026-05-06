<?php

declare(strict_types=1);

namespace Capell\ContentSections\Enums;

use Capell\Admin\Concerns\HasConfiguratorTypes;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;

enum ConfiguratorTypeEnum: string implements ConfiguratorTypeEnumInterface
{
    use HasConfiguratorTypes;

    case Section = 'Sections';

    public function getConfigurators(): array
    {
        return match ($this) {
            self::Section => SectionConfiguratorEnum::cases(),
        };
    }
}
