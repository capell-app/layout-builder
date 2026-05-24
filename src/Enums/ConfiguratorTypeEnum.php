<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;

enum ConfiguratorTypeEnum: string implements ConfiguratorTypeEnumInterface
{
    case LayoutContainer = 'LayoutContainers';

    case LayoutBlock = 'LayoutBlocks';

    case Widget = 'Widgets';

    case WidgetAsset = 'WidgetAssets';

    /**
     * @return array<string, list<class-string<ConfiguratorInterface>>>
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

    public function getConfigurators(): array
    {
        return match ($this) {
            self::LayoutContainer => array_map(
                fn (LayoutContainerConfiguratorEnum $configurator): string => $configurator->value,
                LayoutContainerConfiguratorEnum::cases(),
            ),
            self::LayoutBlock => array_map(
                fn (LayoutBlockConfiguratorEnum $configurator): string => $configurator->value,
                LayoutBlockConfiguratorEnum::cases(),
            ),
            self::Widget => array_map(
                fn (BlockConfiguratorEnum $configurator): string => $configurator->value,
                BlockConfiguratorEnum::cases(),
            ),
            self::WidgetAsset => array_map(
                fn (BlockAssetConfiguratorEnum $configurator): string => $configurator->value,
                BlockAssetConfiguratorEnum::cases(),
            ),
        };
    }

    public function getName(): string
    {
        return $this->name;
    }
}
