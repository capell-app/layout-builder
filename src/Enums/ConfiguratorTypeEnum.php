<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Core\LayoutBuilder\Enums\LayoutContainerConfiguratorEnum;
use Capell\Core\LayoutBuilder\Enums\LayoutWidgetConfiguratorEnum;
use Capell\Core\LayoutBuilder\Enums\WidgetAssetConfiguratorEnum;
use Capell\Core\LayoutBuilder\Enums\WidgetConfiguratorEnum;

enum ConfiguratorTypeEnum: string implements ConfiguratorTypeEnumInterface
{
    case LayoutContainer = 'LayoutContainers';

    case LayoutWidget = 'LayoutWidgets';

    case Widget = 'Widgets';

    case WidgetAsset = 'WidgetAssets';

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
            self::LayoutWidget => LayoutWidgetConfiguratorEnum::cases(),
            self::Widget => WidgetConfiguratorEnum::cases(),
            self::WidgetAsset => WidgetAssetConfiguratorEnum::cases(),
        };
    }

    public function getName(): string
    {
        return $this->name;
    }
}
