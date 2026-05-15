<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;

enum ConfiguratorTypeEnum: string implements ConfiguratorTypeEnumInterface
{
    case LayoutContainer = 'LayoutContainers';

    case LayoutElement = 'LayoutElements';

    case Element = 'Elements';

    case ElementAsset = 'ElementAssets';

    /**
     * @return array<string, array<int, object>>
     */
    public static function getAllConfigurators(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $enum): array => [$enum->value => $enum->getConfigurators()])
            ->all();
    }

    public static function fromName(string $name): ?static
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @return array<int, object>
     */
    public function getConfigurators(): array
    {
        return match ($this) {
            self::LayoutContainer => LayoutContainerConfiguratorEnum::cases(),
            self::LayoutElement => LayoutElementConfiguratorEnum::cases(),
            self::Element => ElementConfiguratorEnum::cases(),
            self::ElementAsset => ElementAssetConfiguratorEnum::cases(),
        };
    }

    public function getName(): string
    {
        return $this->name;
    }
}
