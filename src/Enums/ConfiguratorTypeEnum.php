<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;

enum ConfiguratorTypeEnum: string implements ConfiguratorTypeEnumInterface
{
    case LayoutContainer = 'LayoutContainers';

    case LayoutWidget = 'LayoutWidgets';

    case Widget = 'Widgets';

    case WidgetAsset = 'WidgetAssets';

    /**
     * @return array<string, list<class-string<ConfiguratorInterface>>>
     */
    public static function getAllConfigurators(): array
    {
        $configurators = [];

        foreach (self::cases() as $enum) {
            $configurators[$enum->value] = array_values($enum->getConfigurators());
        }

        return $configurators;
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
        /** @var list<class-string<ConfiguratorInterface>> $configurators */
        $configurators = match ($this) {
            self::LayoutContainer => array_map(
                fn (LayoutContainerConfiguratorEnum $configurator): string => $configurator->value,
                LayoutContainerConfiguratorEnum::cases(),
            ),
            self::LayoutWidget => array_map(
                fn (LayoutWidgetConfiguratorEnum $configurator): string => $configurator->value,
                LayoutWidgetConfiguratorEnum::cases(),
            ),
            self::Widget => array_map(
                fn (WidgetConfiguratorEnum $configurator): string => $configurator->value,
                WidgetConfiguratorEnum::cases(),
            ),
            self::WidgetAsset => array_map(
                fn (WidgetAssetConfiguratorEnum $configurator): string => $configurator->value,
                WidgetAssetConfiguratorEnum::cases(),
            ),
        };

        return $configurators;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
